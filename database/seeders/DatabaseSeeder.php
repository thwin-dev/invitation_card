<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(DemoPosSeeder::class);
        $this->call(RolePermissionSeeder::class);
        $this->call(BulkDemoSeeder::class);
        $this->call(StockMovementBackfillSeeder::class);
        $this->call(ProductInventoryDemoSeeder::class);
    }
}
