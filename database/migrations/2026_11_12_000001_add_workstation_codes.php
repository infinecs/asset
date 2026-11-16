<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Workstation codes follow the office labelling scheme, e.g. INFP1ARAWS01:
     *   INF (company) + location code "P1A" (site P1, block A) + room "RA" + WS + number 01.
     * The generated code is stored in workstations.name.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->unique()->after('name');
        });

        Schema::table('workstations', function (Blueprint $table) {
            $table->string('room', 10)->nullable()->after('name');
            $table->unsignedSmallInteger('number')->nullable()->after('room');
        });
    }

    public function down(): void
    {
        Schema::table('workstations', function (Blueprint $table) {
            $table->dropColumn(['room', 'number']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
