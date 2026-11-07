<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pos_expenses', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->string('period')->nullable();
            $table->date('expense_date');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->boolean('is_profit')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['type', 'expense_date']);
        });
    }

    public function down(): void { Schema::dropIfExists('pos_expenses'); }
};
