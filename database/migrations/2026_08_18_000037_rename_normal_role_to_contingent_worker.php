<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'staff', 'user', 'normal', 'superadmin', 'manager', 'contingent_worker') NOT NULL DEFAULT 'user'");
        DB::table('users')->where('role', 'normal')->update(['role' => 'contingent_worker']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'staff', 'user', 'superadmin', 'manager', 'contingent_worker') NOT NULL DEFAULT 'user'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'staff', 'user', 'normal', 'superadmin', 'manager', 'contingent_worker') NOT NULL DEFAULT 'user'");
        DB::table('users')->where('role', 'contingent_worker')->update(['role' => 'normal']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'staff', 'user', 'normal', 'superadmin', 'manager') NOT NULL DEFAULT 'user'");
    }
};
