<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE sales MODIFY payment_status ENUM('unpaid','processing','partial','paid') NOT NULL DEFAULT 'unpaid'");
    }

    public function down(): void
    {
        DB::statement("UPDATE sales SET payment_status = 'unpaid' WHERE payment_status = 'processing'");
        DB::statement("ALTER TABLE sales MODIFY payment_status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid'");
    }
};
