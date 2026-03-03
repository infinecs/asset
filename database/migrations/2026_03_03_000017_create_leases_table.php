<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->string('lease_number')->unique();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lessee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('lease_start')->nullable();
            $table->date('lease_end')->nullable();
            $table->text('terms')->nullable();
            $table->enum('status', ['pending', 'signed', 'cancelled'])->default('pending');
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_name')->nullable();
            $table->string('signed_ip')->nullable();
            $table->text('signed_user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
