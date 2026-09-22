<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('digital_product_seats');
    }

    public function down(): void
    {
        Schema::create('digital_product_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_product_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['digital_product_id', 'label']);
        });
    }
};
