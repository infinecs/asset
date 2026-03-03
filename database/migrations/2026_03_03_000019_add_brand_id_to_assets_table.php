<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('brand')->constrained('brands')->nullOnDelete();
        });

        $brands = DB::table('assets')
            ->whereNotNull('brand')
            ->select('brand')
            ->distinct()
            ->pluck('brand');

        foreach ($brands as $brandName) {
            $trimmed = trim((string) $brandName);
            if ($trimmed === '') {
                continue;
            }

            $existingId = DB::table('brands')->where('name', $trimmed)->value('id');
            $brandId = $existingId ?: DB::table('brands')->insertGetId([
                'name' => $trimmed,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('assets')
                ->where('brand', $brandName)
                ->update(['brand_id' => $brandId]);
        }
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
        });
    }
};
