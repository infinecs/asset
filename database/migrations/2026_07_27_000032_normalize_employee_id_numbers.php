<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $employees = DB::table('employees')->select('id', 'id_number')->orderBy('id')->get();
        $normalized = [];
        $seen = [];

        foreach ($employees as $employee) {
            $suffix = preg_replace('/^(?:INF-?)+/i', '', trim((string) $employee->id_number));

            if ($suffix === '') {
                throw new RuntimeException("Employee {$employee->id} has an invalid ID number.");
            }

            $idNumber = 'INF' . $suffix;
            $key = strtolower($idNumber);

            if (isset($seen[$key])) {
                throw new RuntimeException("Employee ID normalization would duplicate {$idNumber} for employees {$seen[$key]} and {$employee->id}.");
            }

            $seen[$key] = $employee->id;
            $normalized[$employee->id] = $idNumber;
        }

        foreach ($normalized as $id => $idNumber) {
            DB::table('employees')->where('id', $id)->update(['id_number' => $idNumber]);
        }
    }

    public function down(): void
    {
        // The previous values were inconsistent and cannot be recovered reliably.
    }
};
