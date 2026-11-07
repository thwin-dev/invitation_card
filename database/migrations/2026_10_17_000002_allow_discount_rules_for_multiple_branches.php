<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_rule_branches', function (Blueprint $table): void {
            $table->foreignId('discount_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('inventory_branches')->cascadeOnDelete();
            $table->primary(['discount_rule_id', 'branch_id']);
            $table->index('branch_id');
        });

        DB::table('discount_rules')->select(['id', 'branch_id'])->orderBy('id')->each(function ($rule): void {
            DB::table('discount_rule_branches')->insert([
                'discount_rule_id' => $rule->id,
                'branch_id' => $rule->branch_id,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_rule_branches');
    }
};
