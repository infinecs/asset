<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('brands')->where('name', 'Microsoft')->exists()) {
            DB::table('brands')->insert([
                'name' => 'Microsoft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!DB::table('categories')->where('name', 'Microsoft desktop license')->exists()) {
            DB::table('categories')->insert([
                'name' => 'Microsoft desktop license',
                'slug' => Str::slug('Microsoft desktop license'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('brands')->where('name', 'Microsoft')->delete();
        DB::table('categories')->where('name', 'Microsoft desktop license')->delete();
    }
};
