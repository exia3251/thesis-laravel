<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storage for the storefront assistant.
 *
 * The conversation lives in the database rather than in the browser because
 * the shop is a multi-page application: moving from the catalogue to a
 * product to the basket is three full page loads, and anything held in
 * JavaScript would be lost at each one. Keeping it here also gives the back
 * office something to read afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        // What the assistant knows how to answer. Editable from the back
        // office, so the wording is not trapped in a source file.
        Schema::create('chat_intents', function (Blueprint $table) {
            $table->id('intent_id');
            $table->string('intent_key', 60)->unique();
            $table->string('category', 40)->default('general');

            // The phrasing shown on a suggestion chip, e.g. "Track my order".
            $table->string('label', 120);

            // Space-separated trigger words. Matching is scored against these.
            $table->text('keywords');

            // Exactly one of these is used. A handler runs code and looks
            // something up; an answer is fixed text.
            $table->string('handler', 60)->nullable();
            $table->text('answer')->nullable();

            // Whether the question only makes sense for a signed-in customer.
            $table->boolean('requires_login')->default(false);

            // Offered as an opening suggestion rather than only matched.
            $table->boolean('is_suggested')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->timestamps();

            $table->index(['is_active', 'is_suggested']);
        });

        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id('conversation_id');

            // Null for a visitor who has not signed in. The token is what
            // ties their messages together until they do.
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('visitor_token', 64)->index();

            // Where a multi-step flow, such as the product finder, has got to.
            $table->json('context')->nullable();

            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->nullOnDelete();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->unsignedBigInteger('conversation_id');
            $table->enum('role', ['user', 'bot']);
            $table->text('body');

            // Which intent answered this. Null on a visitor message means
            // nothing matched, which is what the unanswered report reads.
            $table->string('intent_key', 60)->nullable();

            // Chips, product cards and links that accompany a reply.
            $table->json('payload')->nullable();

            $table->timestamps();

            $table->foreign('conversation_id')->references('conversation_id')
                ->on('chat_conversations')->cascadeOnDelete();

            $table->index(['role', 'intent_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
        Schema::dropIfExists('chat_intents');
    }
};
