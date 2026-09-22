<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Products and accounts were deleted outright, which two things made
 * unworkable: the rows are referenced by restricted foreign keys from sales,
 * stock movements and the activity log, so the delete usually failed outright;
 * and where it succeeded it took history with it, leaving old receipts unable
 * to name what had been bought or who had sold it.
 *
 * Both tables now carry deleted_at, so removing either archives the row and
 * leaves every reference to it intact.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
