<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The places an order can be delivered to.
 *
 * Province, city or municipality, and barangay were three free-text boxes, so
 * the same address arrived spelled six ways -- "Imus", "imus city", "Imus,
 * Cavite" -- and a barangay could be typed that does not exist in the city
 * beside it. Nothing downstream could group by area, and a courier had to read
 * whatever was typed.
 *
 * These are the Philippine Statistics Authority's own lists, from the
 * Philippine Standard Geographic Code: 84 provinces, 1,634 cities and
 * municipalities, 42,046 barangays. Held as one table rather than three
 * because every row is the same shape -- a code, a name, and the code of the
 * place it sits inside -- and one table is one thing to explain.
 *
 * Nothing here belongs to this business, so it is reference data: seeded from
 * database/data/psgc.csv, never edited in the back office, and safe to drop
 * and rebuild.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('psgc_locations', function (Blueprint $table) {
            // The PSA's own code, so a row can be matched back to the source
            // list and the pair (city, barangay) can be checked by code
            // rather than by comparing names.
            $table->char('code', 9)->primary();

            $table->string('name', 120);
            $table->enum('level', ['province', 'city', 'barangay']);

            // Null for a province. A city points at its province, a barangay
            // at its city -- which is what makes the three boxes cascade.
            $table->char('parent_code', 9)->nullable();

            // The two questions asked of this table: "every province" and
            // "everything inside this one", both wanting them in order.
            $table->index(['level', 'name']);
            $table->index(['parent_code', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psgc_locations');
    }
};
