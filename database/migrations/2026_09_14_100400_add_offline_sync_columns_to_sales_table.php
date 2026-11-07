<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->string('client_transaction_id', 64)->nullable()->unique()->after('status');
            $table->boolean('is_offline_sync')->default(false)->after('client_transaction_id');
            $table->timestamp('offline_created_at')->nullable()->after('is_offline_sync');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropColumn(['client_transaction_id', 'is_offline_sync', 'offline_created_at']);
        });
    }
};
