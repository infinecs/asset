<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('work_location')->constrained('roles')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->after('role_id')->constrained('employees')->nullOnDelete();
            $table->boolean('is_manager')->default(false)->after('manager_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropConstrainedForeignId('manager_id');
            $table->dropColumn('is_manager');
        });
    }
};
