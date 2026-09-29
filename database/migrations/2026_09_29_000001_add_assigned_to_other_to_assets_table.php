<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Free-text assignee for assets used by the office rather than an employee
            // (e.g. "Security PC - Block A"). Mutually exclusive with assigned_to.
            $table->string('assigned_to_other')->nullable()->after('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('assigned_to_other');
        });
    }
};
