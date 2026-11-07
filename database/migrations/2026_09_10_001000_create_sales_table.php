<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->id();
            $table->string('voucher_code')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('pos_customers')->nullOnDelete();
            $table->foreignId('sale_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('total_quantity')->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->date('sale_date');
            $table->string('status')->default('completed');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('sales'); }
};
