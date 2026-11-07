<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_unit_relations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('from_unit_id')->constrained('product_units')->cascadeOnDelete();
            $table->foreignId('to_unit_id')->constrained('product_units')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['from_unit_id', 'to_unit_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('product_unit_relations'); }
};
