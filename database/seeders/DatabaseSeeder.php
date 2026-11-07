<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Customer\Models\PosCustomer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'user_code' => 'ADMIN-001',
                'name' => 'System Admin',
                'phone' => '09123456789',
                'password' => Hash::make('password123'),
                'role' => 'Admin',
                'status' => 'active',
                'prohibition_flag' => false,
            ]
        );

        $demoCustomers = [
            [
                'name' => 'Daw Mya Mya',
                'phone' => '09450011223',
                'address' => 'No. 12, Inya Road, Yangon',
                'customer_level' => 3,
                'logo_path' => 'customers/logos/sample_logo.svg',
                'qr_code_path' => 'customers/qr_codes/sample_qr.svg',
            ],
            [
                'name' => 'U Kyaw Swar',
                'phone' => '09790022334',
                'address' => 'Corner of 78th & 30th St, Mandalay',
                'customer_level' => 2,
                'logo_path' => null,
                'qr_code_path' => 'customers/qr_codes/sample_qr.svg',
            ],
            [
                'name' => 'Ma Hnin Wai',
                'phone' => '09960033445',
                'address' => 'Pyay Road, Kamayut, Yangon',
                'customer_level' => 1,
                'logo_path' => null,
                'qr_code_path' => null,
            ],
        ];

        foreach ($demoCustomers as $customer) {
            PosCustomer::updateOrCreate(
                ['phone' => $customer['phone']],
                [
                    ...$customer,
                    'created_by' => $admin->id,
                ]
            );
        }
    }
}
