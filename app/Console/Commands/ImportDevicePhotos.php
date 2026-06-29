<?php

namespace App\Console\Commands;

use App\Models\Asset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportDevicePhotos extends Command
{
    protected $signature = 'assets:import-photos {--dry-run : Preview matches without making changes}';
    protected $description = 'Match device images in public/images to assets by brand+model and import them';

    private array $skip = ['infinecs-logo-white.png', 'logo.png'];

    public function handle(): int
    {
        $imageDir = public_path('images');
        $dryRun   = $this->option('dry-run');

        $files = collect(glob($imageDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE))
            ->reject(fn($f) => in_array(basename($f), $this->skip));

        if ($files->isEmpty()) {
            $this->warn('No image files found in public/images.');
            return 1;
        }

        // Build a lookup: "brand model" => [assets]
        $assetsByModel = Asset::whereNotNull('brand')->whereNotNull('model')
            ->get()
            ->groupBy(fn($a) => strtolower(trim($a->brand . ' ' . $a->model)));

        $totalUpdated = 0;
        $unmatched    = [];

        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $key      = strtolower(pathinfo($filename, PATHINFO_FILENAME));

            // 1. Exact match
            $group = $assetsByModel->get($key);

            // 2. Fuzzy: filename words present in brand+model
            if (! $group) {
                $words = array_filter(explode(' ', $key), fn($w) => strlen($w) > 2);
                $best  = null;
                $bestScore = 0;

                foreach ($assetsByModel as $modelKey => $assets) {
                    $score = 0;
                    foreach ($words as $word) {
                        if (str_contains($modelKey, $word)) {
                            $score++;
                        }
                    }
                    if ($score > $bestScore && $score >= ceil(count($words) * 0.6)) {
                        $bestScore = $score;
                        $best      = $assets;
                    }
                }
                $group = $best;
            }

            if (! $group || $group->isEmpty()) {
                $unmatched[] = $filename;
                $this->line("  <fg=yellow>NO MATCH</>  — {$filename}");
                continue;
            }

            $dest = 'assets/photos/' . $filename;

            if (! $dryRun) {
                Storage::disk('public')->put($dest, file_get_contents($filePath));
                foreach ($group as $asset) {
                    $asset->update(['photo_path' => $dest]);
                }
            }

            $tags = $group->pluck('asset_tag')->join(', ');
            $label = $group->first()->brand . ' ' . $group->first()->model;
            $count = $group->count();
            $this->line("  <fg=green>MATCHED</>   — {$filename}  →  {$count} asset(s) [{$tags}]" . ($dryRun ? ' (dry run)' : ''));
            $totalUpdated += $count;
        }

        $this->newLine();
        $this->info("Done. {$totalUpdated} asset(s) updated across " . ($files->count() - count($unmatched)) . " image(s).");

        if (! empty($unmatched)) {
            $this->warn('Unmatched: ' . implode(', ', $unmatched));
        }

        return 0;
    }
}
