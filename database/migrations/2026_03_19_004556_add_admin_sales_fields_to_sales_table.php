<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('customer_name', 255)->nullable()->after('user_id');
            $table->string('payment_method', 50)->nullable()->after('total_amount');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid')->after('payment_method');
            $table->decimal('paid_amount', 10, 2)->default(0)->after('payment_status');
            $table->decimal('balance_due', 10, 2)->default(0)->after('paid_amount');
            $table->enum('delivery_status', ['to_deliver', 'to_receive', 'delivered'])->default('to_deliver')->after('balance_due');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'customer_name',
                'payment_method',
                'payment_status',
                'paid_amount',
                'balance_due',
                'delivery_status',
            ]);
        });
    }
};
