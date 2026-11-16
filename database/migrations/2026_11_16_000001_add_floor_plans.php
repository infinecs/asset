<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // Image of the office layout (public disk); workstations are pinned onto it.
            $table->string('floor_plan_path')->nullable()->after('description');
        });

        Schema::table('workstations', function (Blueprint $table) {
            // Position on the location's floor plan, as a percentage of the image's width/height
            // (0–100), so markers stay in place at any screen size. Null = not placed yet.
            $table->decimal('plan_x', 6, 3)->nullable()->after('notes');
            $table->decimal('plan_y', 6, 3)->nullable()->after('plan_x');
        });
    }

    public function down(): void
    {
        Schema::table('workstations', function (Blueprint $table) {
            $table->dropColumn(['plan_x', 'plan_y']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('floor_plan_path');
        });
    }
};
