<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_card_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_in_charge_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_settings');
    }
};
