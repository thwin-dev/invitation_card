<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->constrained('product_categories')->restrictOnDelete();
            $table->foreignId('subcategory_id')->constrained('product_subcategories')->restrictOnDelete();
            $table->boolean('customer_visible')->default(true);
            $table->string('photo_path')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['category_id', 'subcategory_id']);
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
