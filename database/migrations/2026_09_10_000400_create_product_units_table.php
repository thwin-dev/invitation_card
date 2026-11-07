<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('original_code')->nullable();
            $table->string('name');
            $table->integer('current_quantity')->default(0);
            $table->integer('reorder_quantity')->default(0);
            $table->decimal('normal_sale_price', 12, 2)->default(0);
            $table->decimal('whole_sale_price', 12, 2)->default(0);
            $table->decimal('order_price', 12, 2)->default(0);
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['product_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};
