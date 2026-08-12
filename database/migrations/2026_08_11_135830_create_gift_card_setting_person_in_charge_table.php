<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_card_setting_person_in_charge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_card_setting_id')->constrained('gift_card_settings')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['gift_card_setting_id', 'employee_id'], 'gcs_pic_setting_employee_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_setting_person_in_charge');
    }
};
