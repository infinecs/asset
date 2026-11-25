<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workstations', function (Blueprint $table) {
            // How the desk's marker is turned on the floor plan, in degrees clockwise:
            // 0 = horizontal (as drawn), 90 = vertical. plan_w / plan_h stay the desk's own
            // length and depth; the marker is rotated around its centre.
            $table->unsignedSmallInteger('plan_rotation')->default(0)->after('plan_h');
        });
    }

    public function down(): void
    {
        Schema::table('workstations', function (Blueprint $table) {
            $table->dropColumn('plan_rotation');
        });
    }
};
