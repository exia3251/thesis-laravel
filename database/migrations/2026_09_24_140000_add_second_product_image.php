<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A second photograph, shown only on the product page.
 *
 * The catalogue card and the cart row want one clear picture of the bottle.
 * Canroyal's group shots -- several bottles at an angle -- read as blue
 * smudges at card size, so the single upright bottle belongs there instead.
 * The group shot is still worth seeing once someone has opened the product,
 * because it shows the range of pack sizes, so it moves here rather than
 * being thrown away.
 *
 * One extra column rather than a product_images table: there are two
 * pictures, not a gallery, and a table would be three joins in search of a
 * problem nobody has yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('image_path_2', 255)->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image_path_2');
        });
    }
};
