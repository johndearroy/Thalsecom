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
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['addition', 'deduction', 'adjustment', 'return']);
            $table->integer('quantity'); // positive for addition, negative for deduction
            $table->integer('previous_stock');
            $table->integer('new_stock');
            $table->string('reason')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for reporting and tracking
            $table->index('product_variant_id');
            $table->index('order_id');
            $table->index('created_at');
        });

        // Create a separate table for low stock alerts tracking
        Schema::create('low_stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->integer('current_stock');
            $table->integer('threshold')->default(10);
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index('product_variant_id');
            $table->index('is_resolved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('low_stock_alerts');
        Schema::dropIfExists('inventory_logs');
    }
};
