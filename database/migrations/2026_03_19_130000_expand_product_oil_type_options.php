<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE products MODIFY oil_type ENUM('Synthetic','Semi-Synthetic','Mineral','Coolant','Other') NOT NULL DEFAULT 'Mineral'");
    }

    public function down(): void
    {
        DB::statement("UPDATE products SET oil_type = 'Mineral' WHERE oil_type IN ('Coolant','Other')");
        DB::statement("ALTER TABLE products MODIFY oil_type ENUM('Synthetic','Semi-Synthetic','Mineral') NOT NULL DEFAULT 'Mineral'");
    }
};
