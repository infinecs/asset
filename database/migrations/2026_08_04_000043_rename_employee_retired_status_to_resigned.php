<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('employees')->where('status', 'retired')->update(['status' => 'resigned']);
    }

    public function down(): void
    {
        DB::table('employees')->where('status', 'resigned')->update(['status' => 'retired']);
    }
};
