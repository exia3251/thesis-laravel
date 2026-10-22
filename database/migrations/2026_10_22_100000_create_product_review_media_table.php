<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Photographs and clips attached to a review.
 *
 * A table of its own rather than columns on the review, because a review
 * carries several and the number is a setting that may change. Two columns
 * named media_1 and media_2 would make changing it a migration.
 *
 * mime_type is stored as the server read it at upload, not as the browser
 * claimed. It is what decides whether the page renders an <img> or a
 * <video>, so a wrong one would show a blank frame where a photograph is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_review_media', function (Blueprint $table) {
            $table->id('media_id');

            $table->unsignedBigInteger('review_id');

            // photo or video. Kept as a short word rather than derived from
            // the mime type on every read.
            $table->string('kind', 10);

            $table->string('path', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');

            $table->timestamps();

            $table->index('review_id');

            // A review removed takes its files' rows with it. The files
            // themselves are tidied by the code that deletes the review,
            // which is the only place that knows where they live.
            $table->foreign('review_id')
                ->references('review_id')->on('product_reviews')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_review_media');
    }
};
