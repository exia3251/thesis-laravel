<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which oil grade a vehicle takes.
 *
 * This is reference data about other manufacturers' engines, not a fact about
 * this business, so two columns exist purely to keep it honest: is_verified
 * records whether someone here has checked the row against an actual owner's
 * manual, and source records where the figure came from. Seeded rows start
 * unverified on purpose. Recommending the wrong grade damages an engine, so
 * an unchecked row is never presented as settled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_specs', function (Blueprint $table) {
            $table->id('spec_id');

            $table->string('make', 40);
            $table->string('model', 60);

            // Distinguishes engines within one model, e.g. the 2.4 diesel
            // from the 1.5 petrol, which often take different grades.
            $table->string('variant', 60)->nullable();
            $table->enum('fuel', ['gasoline', 'diesel'])->default('gasoline');

            // Generations matter: a 2012 Hilux and a 2022 Hilux differ.
            $table->smallInteger('year_from')->nullable();
            $table->smallInteger('year_to')->nullable();

            $table->string('viscosity', 10);

            // A second grade the manual commonly also permits. Offered as a
            // possibility to check, never as a substitute.
            $table->string('viscosity_alt', 10)->nullable();

            $table->string('oil_type', 20)->nullable();
            $table->decimal('capacity_litres', 4, 1)->nullable();

            // Alternate spellings people type: "montero sport", "monterosport".
            $table->string('aliases', 160)->nullable();

            $table->text('notes')->nullable();
            $table->string('source', 120)->nullable();
            $table->boolean('is_verified')->default(false);

            $table->timestamps();

            $table->index(['make', 'model']);
            $table->index('model');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_specs');
    }
};
