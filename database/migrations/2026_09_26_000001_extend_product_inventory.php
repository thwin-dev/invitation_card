<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->constrained('product_brands')->nullOnDelete();
        });
        Schema::table('product_units', function (Blueprint $table) {
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->string('tracking')->default('none');
        });
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('expires_at')->nullable();
        });
        Schema::create('bundle_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_unit_id')->constrained('product_units')->cascadeOnDelete();
            $table->foreignId('component_unit_id')->constrained('product_units')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unique(['bundle_unit_id', 'component_unit_id']);
        });
        Schema::create('inventory_branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_main')->default(false);
            $table->timestamps();
        });
        DB::table('inventory_branches')->insert(['name' => 'Main', 'is_main' => true, 'created_at' => now(), 'updated_at' => now()]);
        Schema::create('inventory_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_unit_id')->constrained('product_units')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('inventory_branches')->restrictOnDelete();
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable()->unique();
            $table->date('expires_at')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();
            $table->index(['branch_id', 'product_unit_id']);
        });
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->constrained('inventory_branches')->restrictOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inventory_lots')->restrictOnDelete();
            $table->string('operation_key')->nullable()->index();
        });
        Schema::create('inventory_operations', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('kind');
            $table->foreignId('user_id')->constrained('users');
            $table->json('details');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', fn (Blueprint $table) => $table->dropColumn(['batch_number', 'serial_number', 'expires_at']));
        Schema::dropIfExists('inventory_operations');
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('lot_id');
            $table->dropColumn('operation_key');
        });
        Schema::dropIfExists('inventory_lots');
        Schema::dropIfExists('inventory_branches');
        Schema::dropIfExists('bundle_components');
        Schema::table('product_units', fn (Blueprint $table) => $table->dropColumn(['size', 'color', 'tracking']));
        Schema::table('products', fn (Blueprint $table) => $table->dropConstrainedForeignId('brand_id'));
        Schema::dropIfExists('product_brands');
    }
};
