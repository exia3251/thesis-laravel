<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A pack size is not a different product.
 *
 * The catalogue used to carry the size in the product name -- "... 5W30 1L"
 * and "... 5W30 4L" were two unrelated rows -- so the shop listed the same
 * oil three times and a customer had to know which line to click.
 *
 * Each row stays one pack size, because price and stock genuinely differ per
 * pack, and the drum is only carried on some lines. What is new is that rows
 * belonging to the same oil now say so, so the storefront can show one card
 * with a size selector on it.
 *
 * Grouping on the name would have worked until someone edited one row's
 * wording and silently split the line in two, so the key is explicit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_line', 100)->nullable()->after('brand')->index();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['product_line']);
            $table->dropColumn('product_line');
        });
    }
};
