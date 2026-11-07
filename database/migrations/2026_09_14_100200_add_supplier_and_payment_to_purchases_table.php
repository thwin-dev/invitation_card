<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table): void {
            $table->foreignId('supplier_id')->nullable()->after('id')->constrained('pos_suppliers')->nullOnDelete();
            $table->decimal('paid_amount', 12, 2)->default(0)->after('total_price');
            $table->decimal('payable_amount', 12, 2)->default(0)->after('paid_amount');
            $table->string('payment_status', 30)->default('paid')->after('payable_amount');
            $table->date('payment_due_date')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn(['paid_amount', 'payable_amount', 'payment_status', 'payment_due_date']);
        });
    }
};
