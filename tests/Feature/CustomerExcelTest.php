<?php

namespace Tests\Feature;

use App\Livewire\CustomerTable;
use App\Models\User;
use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Customer\Services\CustomerExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerExcelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'user_code' => 'ADMIN-001',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'Admin',
            'status' => 'active',
            'prohibition_flag' => false,
        ]);
    }

    public function test_sample_excel_can_be_downloaded(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->get(route('management.customers.sample-excel'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_excel_service_imports_file_successfully(): void
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'cust_test_').'.xlsx';
        $service = new CustomerExcelService();
        $service->saveSampleFile($tempPath);

        $result = $service->import($tempPath, $this->admin->id);

        $this->assertEquals(3, $result['imported']);
        $this->assertDatabaseHas('pos_customers', [
            'name' => 'Daw Mya Mya',
            'phone' => '09450011223',
            'customer_level' => 3,
        ]);

        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
    }

    public function test_customer_import_via_controller(): void
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'cust_ctrl_').'.xlsx';
        $service = new CustomerExcelService();
        $service->saveSampleFile($tempPath);

        $file = new UploadedFile(
            $tempPath,
            'customer_import_sample.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($this->admin, 'web')
            ->post(route('management.customers.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('management.customers.index'));
        $this->assertDatabaseHas('pos_customers', [
            'name' => 'Daw Mya Mya',
            'phone' => '09450011223',
        ]);

        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
    }

    public function test_customer_import_via_livewire(): void
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'cust_lw_').'.xlsx';
        $service = new CustomerExcelService();
        $service->saveSampleFile($tempPath);

        $file = UploadedFile::fake()->createWithContent('import.xlsx', file_get_contents($tempPath));

        $this->actingAs($this->admin, 'web');

        Livewire::test(CustomerTable::class)
            ->set('importFile', $file)
            ->call('importCustomers', $service)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('pos_customers', [
            'name' => 'Daw Mya Mya',
        ]);

        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
    }
}
