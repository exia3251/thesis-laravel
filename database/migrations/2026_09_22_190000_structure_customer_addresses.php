<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Breaks the single free-text address into the parts a Philippine delivery
 * address actually has.
 *
 * One blob cannot be sorted, grouped or checked. Separate fields mean the
 * analytics screen can say where orders go, and a customer cannot leave out
 * the barangay, which is the line couriers here most need.
 *
 * The old column is dropped rather than kept in step with the new ones. Two
 * copies of the same fact drift, which is the same reason the duplicate email
 * came off this table earlier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->string('house_street', 160)->nullable()->after('phone');
            $table->string('barangay', 100)->nullable()->after('house_street');
            $table->string('city', 100)->nullable()->after('barangay');
            $table->string('province', 100)->nullable()->after('city');
            $table->string('postal_code', 10)->nullable()->after('province');
        });

        // Existing addresses are free text, so this is a best effort: a
        // comma-separated one is split from the right, since province and
        // city come last by convention. Anything else lands whole in the
        // street line, which is visible and editable rather than lost.
        foreach (DB::table('customer_profiles')->select('profile_id', 'address')->get() as $row) {
            $parts = array_values(array_filter(array_map('trim', explode(',', (string) $row->address))));

            $update = match (true) {
                count($parts) >= 3 => [
                    'house_street' => implode(', ', array_slice($parts, 0, count($parts) - 2)),
                    'city' => $parts[count($parts) - 2],
                    'province' => $parts[count($parts) - 1],
                ],
                count($parts) === 2 => [
                    'house_street' => $parts[0],
                    'city' => $parts[1],
                ],
                default => ['house_street' => $parts[0] ?? ''],
            };

            DB::table('customer_profiles')->where('profile_id', $row->profile_id)->update($update);
        }

        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->text('address')->nullable()->after('phone');
        });

        DB::statement("
            UPDATE customer_profiles
            SET address = CONCAT_WS(', ',
                NULLIF(house_street, ''),
                NULLIF(barangay, ''),
                NULLIF(city, ''),
                NULLIF(province, ''),
                NULLIF(postal_code, '')
            )
        ");

        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropColumn(['house_street', 'barangay', 'city', 'province', 'postal_code']);
        });
    }
};
