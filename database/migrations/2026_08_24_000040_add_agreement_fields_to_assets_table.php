<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('agreement_token')->nullable()->unique()->after('signed_document_path');
            $table->timestamp('agreement_sent_at')->nullable()->after('agreement_token');
            $table->timestamp('agreement_signed_at')->nullable()->after('agreement_sent_at');
            $table->string('agreement_signature_path')->nullable()->after('agreement_signed_at');
            $table->string('agreement_signed_name')->nullable()->after('agreement_signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'agreement_token',
                'agreement_sent_at',
                'agreement_signed_at',
                'agreement_signature_path',
                'agreement_signed_name',
            ]);
        });
    }
};
