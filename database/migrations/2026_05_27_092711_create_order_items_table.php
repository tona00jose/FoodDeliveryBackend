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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate()
                  ->unique();
            $table->foreignId('meal_id')
                  ->constrained('meals')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate()
                  ->unique();
            $table->integer('quantity')->default(1);
            $table->decimal('price_at_order_time', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0); // quantity * price_at_order_time
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
