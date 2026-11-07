<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table): void {
            $table->id();
            $table->string('supplier_name');
            $table->unsignedInteger('total_quantity')->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->date('purchase_date');
            $table->foreignId('purchase_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('purchases'); }
};
