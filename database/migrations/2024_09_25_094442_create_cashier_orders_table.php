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
        Schema::create('cashier_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('cashier_id');
            $table->integer('delivery_user_id');
            $table->integer('order_id');
            $table->enum('pickup_status', ['pending','pickedup']);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_orders');
    }
};
