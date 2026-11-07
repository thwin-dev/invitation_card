<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('inventory_branches')->restrictOnDelete();
            $table->foreignId('product_unit_id')->nullable()->constrained('product_units')->restrictOnDelete();
            $table->string('name', 100);
            $table->string('type', 20);
            $table->decimal('value', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['branch_id', 'is_active']);
        });
        Schema::table('sales', function (Blueprint $table): void {
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
        });
        DB::table('sales')->update(['subtotal' => DB::raw('total_price')]);
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->foreignId('discount_rule_id')->nullable()->constrained('discount_rules')->nullOnDelete();
            $table->string('discount_name', 100)->nullable();
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('discount_rule_id');
            $table->dropColumn(['discount_name', 'discount_type', 'discount_value', 'discount_amount']);
        });
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn(['subtotal', 'discount_amount']));
        Schema::dropIfExists('discount_rules');
    }
};
