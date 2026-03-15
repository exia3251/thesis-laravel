<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Suppliers table
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id('supplier_id');
            $table->string('supplier_name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        // Products table  
        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id');
            $table->string('product_name');
            $table->string('brand');
            $table->enum('oil_type', ['Synthetic', 'Semi-Synthetic', 'Mineral', 'Other']);
            $table->string('viscosity_grade')->nullable();
            $table->string('unit');
            $table->decimal('price', 10, 2);
            $table->integer('reorder_level')->default(10);
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers', 'supplier_id')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('product_name');
            $table->index('brand');
            $table->index('oil_type');
        });

        // Inventory table
        Schema::create('inventory', function (Blueprint $table) {
            $table->id('inventory_id');
            $table->foreignId('product_id')->constrained('products', 'product_id')->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->timestamp('last_updated')->useCurrent();
            
            $table->index('product_id');
            $table->index('quantity');
        });

        // Stock transactions table
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->foreignId('product_id')->constrained('products', 'product_id')->cascadeOnDelete();
            $table->enum('transaction_type', ['IN', 'OUT', 'SALE', 'ADJUSTMENT']);
            $table->integer('quantity');
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamp('transaction_date')->useCurrent();
            
            $table->index(['product_id', 'transaction_date']);
            $table->index('transaction_type');
        });

        // Sales table
        Schema::create('sales', function (Blueprint $table) {
            $table->id('sale_id');
            $table->string('customer_name');
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method');
            $table->enum('status', ['Pending', 'Processing', 'Completed', 'Cancelled'])->default('Pending');
            $table->timestamp('sale_date')->useCurrent();
            
            $table->index('user_id');
            $table->index('sale_date');
            $table->index('status');
        });

        // Sale items table
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id('sale_item_id');
            $table->foreignId('sale_id')->constrained('sales', 'sale_id')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            
            $table->index('sale_id');
            $table->index('product_id');
        });

        // Customer profiles table
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id('customer_id');
            $table->foreignId('user_id')->unique()->constrained('users', 'user_id')->cascadeOnDelete();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
        });

        // Shopping cart table
        Schema::create('shopping_cart', function (Blueprint $table) {
            $table->id('cart_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'product_id')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->timestamp('added_at')->useCurrent();
            
            $table->unique(['user_id', 'product_id']);
            $table->index('user_id');
        });

        // Activity logs table
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->string('action', 50);
            $table->string('table_name', 50)->nullable();
            $table->integer('record_id')->nullable();
            $table->text('description')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('shopping_cart');
        Schema::dropIfExists('customer_profiles');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('stock_transactions');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('products');
        Schema::dropIfExists('suppliers');
    }
};
