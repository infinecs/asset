<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('join_date')->nullable()->after('status');
            $table->date('resigned_date')->nullable()->after('join_date');
        });

        // Backfill resigned employees: use the date their status was last changed to resigned
        // (from the activity history), falling back to when the record was last updated.
        $resigned = DB::table('employees')->where('status', 'resigned')->get(['id', 'updated_at']);

        foreach ($resigned as $employee) {
            $changedAt = DB::table('employee_histories')
                ->where('employee_id', $employee->id)
                ->whereIn('action', ['status_changed', 'updated'])
                ->where('changes', 'like', '%"new":"resigned"%')
                ->latest('created_at')
                ->value('created_at');

            $date = $changedAt ?? $employee->updated_at;

            if ($date) {
                DB::table('employees')->where('id', $employee->id)->update(['resigned_date' => substr($date, 0, 10)]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['join_date', 'resigned_date']);
        });
    }
};
