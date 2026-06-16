<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('cpu')->nullable()->after('notes');
            $table->string('ram')->nullable()->after('cpu');
            $table->string('storage')->nullable()->after('ram');
            $table->string('display')->nullable()->after('storage');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['cpu', 'ram', 'storage', 'display']);
        });
    }
};
