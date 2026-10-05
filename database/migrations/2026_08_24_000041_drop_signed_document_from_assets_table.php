<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('assets')->whereNotNull('signed_document_path')->pluck('signed_document_path')->each(function ($path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('signed_document_path');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('signed_document_path')->nullable()->after('serial_number');
        });
    }
};
