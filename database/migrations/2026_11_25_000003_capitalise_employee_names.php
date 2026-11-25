<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Employee names are stored in capital letters (see Employee::normalizeName()); bring the
 * existing records in line. Done row by row in PHP so it matches the model exactly (trimmed,
 * single-spaced, multibyte-safe capitals) on any database.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('employees')->select(['id', 'name'])->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                $name = $row->name === null ? null : mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $row->name)));
                if ($name !== $row->name) {
                    DB::table('employees')->where('id', $row->id)->update(['name' => $name]);
                }
            }
        });
    }

    public function down(): void
    {
        // The original mixed-case spelling isn't kept, so there's nothing to restore.
    }
};
