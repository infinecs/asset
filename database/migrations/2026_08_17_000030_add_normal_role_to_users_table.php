<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'staff', 'user', 'normal') NOT NULL DEFAULT 'user'");
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'normal')->update(['role' => 'staff']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'staff', 'user') NOT NULL DEFAULT 'user'");
    }
};
