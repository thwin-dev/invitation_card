<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'user_code')) {
                $table->string('user_code')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->nullable()->after('password');
            }
            if (! Schema::hasColumn('users', 'prohibition_flag')) {
                $table->boolean('prohibition_flag')->default(false)->after('role');
            }
            if (! Schema::hasColumn('users', 'photo_path')) {
                $table->string('photo_path')->nullable()->after('prohibition_flag');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach (['user_code', 'role', 'prohibition_flag', 'photo_path'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
