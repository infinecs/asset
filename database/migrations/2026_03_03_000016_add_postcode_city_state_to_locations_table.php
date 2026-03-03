<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('postcode', 20)->nullable()->after('room');
            $table->string('city', 100)->nullable()->after('postcode');
            $table->string('state', 100)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['postcode', 'city', 'state']);
        });
    }
};
