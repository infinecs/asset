<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every time a fixed asset changes workstation. The *_label columns keep a snapshot of the
        // workstation name (e.g. "Infinecs P1 HQ · WS-01") so the log still reads correctly after a
        // workstation is renamed or deleted.
        Schema::create('asset_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_workstation_id')->nullable()->constrained('workstations')->nullOnDelete();
            $table->foreignId('to_workstation_id')->nullable()->constrained('workstations')->nullOnDelete();
            $table->string('from_label')->nullable();
            $table->string('to_label')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_movements');
    }
};
