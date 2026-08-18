<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outcome_tasks', function (Blueprint $table) {
            $table->dropColumn('job_title');
        });
    }

    public function down(): void
    {
        Schema::table('outcome_tasks', function (Blueprint $table) {
            $table->string('job_title')->nullable()->after('department');
        });
    }
};
