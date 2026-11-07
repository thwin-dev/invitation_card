<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->decimal('paid_amount', 12, 2)->default(0)->after('total_price');
            $table->decimal('credit_amount', 12, 2)->default(0)->after('paid_amount');
            $table->date('repayment_date')->nullable()->after('credit_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void { $table->dropColumn(['paid_amount', 'credit_amount', 'repayment_date']); });
    }
};
