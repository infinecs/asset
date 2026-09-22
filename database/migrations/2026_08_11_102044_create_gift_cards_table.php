<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('occasion_year');
            $table->date('birthday_date');
            $table->string('gift_card_code')->nullable();
            $table->enum('status', ['pending', 'reminding', 'ready', 'sent'])->default('pending');
            $table->unsignedInteger('reminder_count')->default(0);
            $table->timestamp('last_reminded_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'occasion_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_cards');
    }
};
