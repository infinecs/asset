<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // When someone marked the new joiner's onboarding checklist done. Null = still being
            // set up (only shown for people who joined recently; see Employee::isOnboarding()).
            $table->timestamp('onboarded_at')->nullable()->after('resigned_date');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('onboarded_at');
        });
    }
};
