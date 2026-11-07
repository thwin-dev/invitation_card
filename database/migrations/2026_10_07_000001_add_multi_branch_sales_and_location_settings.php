<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_branches', function (Blueprint $table): void {
            $table->string('type')->default('shop');
            $table->boolean('is_active')->default(true);
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('currency_code', 3)->default('MMK');
            $table->text('receipt_header')->nullable();
            $table->text('receipt_footer')->nullable();
        });

        DB::table('inventory_branches')->where('is_main', true)->update(['type' => 'shop']);

        Schema::create('branch_unit_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('inventory_branches')->cascadeOnDelete();
            $table->foreignId('product_unit_id')->constrained('product_units')->cascadeOnDelete();
            $table->decimal('normal_sale_price', 12, 2)->nullable();
            $table->decimal('whole_sale_price', 12, 2)->nullable();
            $table->timestamps();
            $table->unique(['branch_id', 'product_unit_id']);
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->foreignId('branch_id')->nullable()->after('sale_by')->constrained('inventory_branches')->nullOnDelete();
            $table->string('branch_name_snapshot')->nullable();
            $table->string('branch_phone_snapshot')->nullable();
            $table->text('branch_address_snapshot')->nullable();
            $table->string('currency_code_snapshot', 3)->default('MMK');
            $table->text('receipt_header_snapshot')->nullable();
            $table->text('receipt_footer_snapshot')->nullable();
        });

        $mainBranch = DB::table('inventory_branches')->where('is_main', true)->first();
        if ($mainBranch) {
            DB::table('sales')->whereNull('branch_id')->update([
                'branch_id' => $mainBranch->id,
                'branch_name_snapshot' => $mainBranch->name,
                'branch_phone_snapshot' => $mainBranch->phone,
                'branch_address_snapshot' => $mainBranch->address,
                'currency_code_snapshot' => $mainBranch->currency_code ?: 'MMK',
                'receipt_header_snapshot' => $mainBranch->receipt_header,
                'receipt_footer_snapshot' => $mainBranch->receipt_footer,
            ]);

            DB::table('stock_movements')->whereNull('branch_id')->update(['branch_id' => $mainBranch->id]);
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn([
                'branch_name_snapshot', 'branch_phone_snapshot', 'branch_address_snapshot',
                'currency_code_snapshot', 'receipt_header_snapshot', 'receipt_footer_snapshot',
            ]);
        });
        Schema::dropIfExists('branch_unit_prices');
        Schema::table('inventory_branches', fn (Blueprint $table) => $table->dropColumn([
            'type', 'is_active', 'phone', 'address', 'currency_code', 'receipt_header', 'receipt_footer',
        ]));
    }
};
