<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Products table (NO supplier_id)
        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id');
            $table->string('product_name', 200);
            $table->string('brand', 100);
            $table->enum('oil_type', ['Synthetic', 'Semi-Synthetic', 'Mineral'])->default('Mineral');
            $table->string('viscosity_grade', 20)->nullable();
            $table->string('unit', 50)->default('1 Liter');
            $table->decimal('price', 10, 2);
            $table->integer('reorder_level')->default(10);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Inventory table
        Schema::create('inventory', function (Blueprint $table) {
            $table->id('inventory_id');
            $table->foreignId('product_id')->constrained('products', 'product_id')->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->timestamp('last_updated')->useCurrent();
            $table->timestamps();
        });

        // Sales table
        Schema::create('sales', function (Blueprint $table) {
            $table->id('sale_id');
            $table->foreignId('user_id')->constrained('users', 'user_id');
            $table->decimal('total_amount', 10, 2);
            $table->timestamp('sale_date')->useCurrent();
            $table->timestamps();
        });

        // Sale Items table
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id('sale_item_id');
            $table->foreignId('sale_id')->constrained('sales', 'sale_id')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products', 'product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        // Stock Transactions table
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->foreignId('product_id')->constrained('products', 'product_id');
            $table->enum('transaction_type', ['stock_in', 'stock_out', 'adjustment']);
            $table->integer('quantity');
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users', 'user_id');
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamps();
        });

        // Customer Profiles table
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id('profile_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->string('phone', 20);
            $table->string('email', 100)->nullable();
            $table->text('address');
            $table->timestamps();
        });

        // Shopping Cart table
        Schema::create('shopping_cart', function (Blueprint $table) {
            $table->id('cart_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products', 'product_id');
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });

        // Activity Logs table
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('user_id')->constrained('users', 'user_id');
            $table->string('action', 100);
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('shopping_cart');
        Schema::dropIfExists('customer_profiles');
        Schema::dropIfExists('stock_transactions');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('products');
    }
};
