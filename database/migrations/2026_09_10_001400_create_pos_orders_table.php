<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pos_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained('pos_customers')->restrictOnDelete();
            $table->text('address');
            $table->string('phone')->nullable();
            $table->dateTime('order_date');
            $table->dateTime('delivered_date')->nullable();
            $table->unsignedInteger('total_quantity')->default(0);
            $table->decimal('estimated_price', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->foreignId('delivery_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('pos_orders'); }
};
