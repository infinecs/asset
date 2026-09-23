<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->timestamp('manager_notified_3d_at')->nullable()->after('last_reminded_at');
            $table->timestamp('manager_notified_2d_at')->nullable()->after('manager_notified_3d_at');
        });
    }

    public function down(): void
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->dropColumn(['manager_notified_3d_at', 'manager_notified_2d_at']);
        });
    }
};
