<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('assets')->select('id', 'asset_tag')->get() as $asset) {
            $normalized = str_replace('ISSB-', 'ISSB', $asset->asset_tag);

            if ($normalized !== $asset->asset_tag) {
                DB::table('assets')->where('id', $asset->id)->update(['asset_tag' => $normalized]);
            }
        }
    }

    public function down(): void
    {
        // Data normalization; not reversible.
    }
};
