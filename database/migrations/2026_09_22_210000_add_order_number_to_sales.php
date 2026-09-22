<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gives every order a reference of its own.
 *
 * Orders were quoted to customers as "#412", which is the primary key. That
 * tells anyone reading it how many orders the business has ever taken, means
 * nothing over the phone, and sits oddly beside the receipt and delivery
 * numbers, which are already issued properly.
 *
 * Existing orders are numbered in the sequence they were placed, and the
 * counter is set to carry on from there rather than starting again at one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('order_no', 24)->nullable()->unique()->after('sale_id');
        });

        $counts = [];

        // Ordered by id, so the reference runs in the order the orders were
        // actually taken rather than in whatever order the rows come back.
        foreach (DB::table('sales')->select('sale_id', 'sale_date')->orderBy('sale_id')->get() as $sale) {
            $year = (int) date('Y', strtotime((string) $sale->sale_date));
            $counts[$year] = ($counts[$year] ?? 0) + 1;

            DB::table('sales')->where('sale_id', $sale->sale_id)->update([
                'order_no' => sprintf('ORD-%d-%06d', $year, $counts[$year]),
            ]);
        }

        foreach ($counts as $year => $last) {
            DB::table('document_sequences')->updateOrInsert(
                ['series' => 'ORD', 'year' => $year],
                ['last_number' => $last, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['order_no']);
            $table->dropColumn('order_no');
        });

        DB::table('document_sequences')->where('series', 'ORD')->delete();
    }
};
