<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Collapses super_admin into admin, introduces the staff roles, and moves
 * the login identifier from username to email.
 *
 * Usernames are dropped rather than kept alongside email, so this is only
 * reversible as far as the schema goes - the username values themselves are
 * gone. A database backup is taken before this runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email', 150)->nullable()->after('username');
        });

        // Customers already gave us an address at registration.
        DB::statement("
            UPDATE users u
            JOIN customer_profiles p ON p.user_id = u.user_id
            SET u.email = p.email
            WHERE p.email IS NOT NULL AND p.email <> ''
        ");

        // Seeded staff accounts have no address anywhere, so give them one.
        DB::table('users')->where('username', 'superadmin')->update(['email' => 'superadmin@raney.test']);
        DB::table('users')->where('username', 'admin')->update(['email' => 'admin@raney.test']);

        // Anyone still without an address gets a placeholder they can change later.
        DB::statement("
            UPDATE users
            SET email = CONCAT('user', user_id, '@raney.test')
            WHERE email IS NULL OR email = ''
        ");

        // Guard against a collision making the unique index impossible to add.
        $duplicates = DB::table('users')
            ->select('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('email');

        foreach ($duplicates as $email) {
            $rows = DB::table('users')->where('email', $email)->orderBy('user_id')->pluck('user_id')->slice(1);

            foreach ($rows as $userId) {
                DB::table('users')->where('user_id', $userId)->update([
                    'email' => preg_replace('/@/', "+{$userId}@", $email, 1),
                ]);
            }
        }

        DB::statement('ALTER TABLE users MODIFY email VARCHAR(150) NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
        });

        // Fold the top tier into admin before the enum stops accepting it.
        DB::table('users')->where('role', 'super_admin')->update(['role' => 'admin']);

        DB::statement("
            ALTER TABLE users
            MODIFY role ENUM('admin', 'inventory_staff', 'accounting', 'customer')
            NOT NULL DEFAULT 'customer'
        ");

        // Accounts created through Google Sign-In will not carry a password.
        DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_unique');
            $table->dropColumn('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->after('user_id');
        });

        // Usernames cannot be recovered; derive something unique from the email.
        DB::statement("UPDATE users SET username = SUBSTRING_INDEX(email, '@', 1)");
        DB::statement("
            UPDATE users u
            JOIN (SELECT user_id FROM users) x ON x.user_id = u.user_id
            SET u.username = CONCAT(u.username, u.user_id)
            WHERE u.username IN (SELECT username FROM (SELECT username FROM users GROUP BY username HAVING COUNT(*) > 1) d)
        ");
        DB::statement('ALTER TABLE users MODIFY username VARCHAR(50) NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
            $table->dropUnique('users_email_unique');
            $table->dropColumn('email');
        });

        DB::statement("
            ALTER TABLE users
            MODIFY role ENUM('super_admin', 'admin', 'customer')
            NOT NULL DEFAULT 'customer'
        ");

        DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL');
    }
};
