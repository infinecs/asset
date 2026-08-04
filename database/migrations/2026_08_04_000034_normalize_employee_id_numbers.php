<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('employees')->select('id', 'id_number')->get() as $employee) {
            $normalized = 'INF' . preg_replace('/\D+/', '', $employee->id_number);

            if ($normalized !== $employee->id_number) {
                DB::table('employees')->where('id', $employee->id)->update(['id_number' => $normalized]);
            }
        }
    }

    public function down(): void
    {
        // Data normalization; not reversible.
    }
};
