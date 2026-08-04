<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE assets MODIFY asset_tag VARCHAR(255) NOT NULL');
        DB::table('categories')->where('name', 'Microsoft desktop license')->delete();
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE assets MODIFY asset_tag VARCHAR(255) NULL');
    }
};
