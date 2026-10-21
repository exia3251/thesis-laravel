<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What customers thought of an oil they actually bought.
 *
 * sale_id is the point of the table. A review is only accepted from somebody
 * whose order containing that product has been delivered, and the order it
 * came from is recorded alongside it -- so "verified purchase" is a fact the
 * row can be checked against, not a badge the shop awards itself.
 *
 * One review per customer per product, enforced by the database rather than
 * only by the form: buying the same oil four times is not four opinions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id('review_id');

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id');

            // The delivered order that entitled this review. Kept even if the
            // customer later buys the product again, because it is the one
            // that was checked at the time.
            $table->unsignedBigInteger('sale_id');

            // 1 to 5. Held as a small integer rather than a decimal: half
            // stars invite an argument about what half a star means.
            $table->unsignedTinyInteger('rating');

            // Optional. A rating on its own is still worth having, and asking
            // for words loses the customers who have none.
            $table->text('body')->nullable();

            $table->timestamps();

            // Removed rather than destroyed, so a review taken down for
            // language can be restored if that was the wrong call, and the
            // count of what was moderated survives.
            $table->softDeletes();

            $table->unique(['user_id', 'product_id'], 'one_review_per_customer_per_product');
            $table->index(['product_id', 'deleted_at']);

            $table->foreign('product_id')->references('product_id')->on('products')->cascadeOnDelete();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
            $table->foreign('sale_id')->references('sale_id')->on('sales')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
