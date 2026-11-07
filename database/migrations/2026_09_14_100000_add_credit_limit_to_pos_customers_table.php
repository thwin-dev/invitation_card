<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pos_customers', function (Blueprint $table): void {
            $table->decimal('credit_limit', 12, 2)->default(0)->after('allow_credit');
        });
    }

    public function down(): void
    {
        Schema::table('pos_customers', function (Blueprint $table): void {
            $table->dropColumn('credit_limit');
        });
    }
};
