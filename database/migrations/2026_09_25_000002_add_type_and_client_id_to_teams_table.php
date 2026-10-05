<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->enum('type', ['internal', 'client_placement'])->default('internal')->after('name');
            $table->foreignId('client_id')->nullable()->after('type')->constrained('clients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn('type');
        });
    }
};
