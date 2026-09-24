<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a password reset link lives between being sent and being used.
 *
 * Laravel's own table, which this project never created: the users table was
 * written by hand rather than taken from the framework's starter migration,
 * and this came with it. config/auth.php has been pointing at a table that
 * did not exist ever since.
 *
 * The token is stored hashed, so the row is of no use to anyone who reads
 * the database -- only the copy in the email opens the account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
