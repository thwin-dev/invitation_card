<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pos_customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('pos_customers', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('customer_level');
            }
            if (! Schema::hasColumn('pos_customers', 'qr_code_path')) {
                $table->string('qr_code_path')->nullable()->after('logo_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_customers', function (Blueprint $table): void {
            $columns = [];
            if (Schema::hasColumn('pos_customers', 'logo_path')) {
                $columns[] = 'logo_path';
            }
            if (Schema::hasColumn('pos_customers', 'qr_code_path')) {
                $columns[] = 'qr_code_path';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
