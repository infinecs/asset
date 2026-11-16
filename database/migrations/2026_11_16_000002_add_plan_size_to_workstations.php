<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workstations', function (Blueprint $table) {
            // Size of the desk's marker on the floor plan, as a percentage of the image's
            // width/height, so it can match the desk's real footprint. Null = default small label.
            $table->decimal('plan_w', 6, 3)->nullable()->after('plan_y');
            $table->decimal('plan_h', 6, 3)->nullable()->after('plan_w');
        });
    }

    public function down(): void
    {
        Schema::table('workstations', function (Blueprint $table) {
            $table->dropColumn(['plan_w', 'plan_h']);
        });
    }
};
