<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outcome_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('category');
            $table->string('department')->nullable();
            $table->string('job_title');
            $table->text('job_description')->nullable();
            $table->decimal('man_hour', 6, 2);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outcome_tasks');
    }
};
