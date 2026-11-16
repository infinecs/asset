<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A desk audit: someone walked up to a workstation and checked its equipment against the
 * records. Missing and found items are stored as snapshots (asset tag + name) so the audit
 * still reads correctly after the assets move on or are deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workstation_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workstation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('present_count')->default(0);
            $table->json('missing')->nullable();
            $table->json('found')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workstation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workstation_audits');
    }
};
