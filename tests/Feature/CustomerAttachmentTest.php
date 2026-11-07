<?php

namespace Tests\Feature;

use App\Livewire\CustomerTable;
use App\Models\User;
use App\Modules\Customer\Models\PosCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

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

    public function test_can_create_customer_with_logo_and_qr_code(): void
    {
        $this->actingAs($this->admin, 'web');

        $logo = UploadedFile::fake()->image('logo.png', 100, 100);
        $qrCode = UploadedFile::fake()->image('qrcode.png', 200, 200);

        Livewire::test(CustomerTable::class)
            ->set('createName', 'Event Host LLC')
            ->set('createPhone', '0912345678')
            ->set('createAddress', 'Downtown Yangon')
            ->set('createCustomerLevel', 3)
            ->set('createLogo', $logo)
            ->set('createQrCode', $qrCode)
            ->call('createCustomer')
            ->assertDispatched('toast');

        $customer = PosCustomer::where('phone', '0912345678')->first();
        $this->assertNotNull($customer);
        $this->assertNotNull($customer->logo_path);
        $this->assertNotNull($customer->qr_code_path);

        Storage::disk('public')->assertExists($customer->logo_path);
        Storage::disk('public')->assertExists($customer->qr_code_path);
    }

    public function test_can_update_customer_and_remove_attachments(): void
    {
        $this->actingAs($this->admin, 'web');

        $logoPath = 'customers/logos/existing_logo.png';
        $qrPath = 'customers/qr_codes/existing_qr.png';
        Storage::disk('public')->put($logoPath, 'fake-logo-content');
        Storage::disk('public')->put($qrPath, 'fake-qr-content');

        $customer = PosCustomer::create([
            'name' => 'Original Customer',
            'phone' => '09988776655',
            'customer_level' => 1,
            'logo_path' => $logoPath,
            'qr_code_path' => $qrPath,
            'created_by' => $this->admin->id,
        ]);

        Livewire::test(CustomerTable::class)
            ->call('editCustomer', $customer->id)
            ->set('removeEditLogo', true)
            ->set('removeEditQrCode', true)
            ->call('updateCustomer')
            ->assertDispatched('toast');

        $customer->refresh();
        $this->assertNull($customer->logo_path);
        $this->assertNull($customer->qr_code_path);
        Storage::disk('public')->assertMissing($logoPath);
        Storage::disk('public')->assertMissing($qrPath);
    }

    public function test_can_update_customer_with_new_replacement_attachments(): void
    {
        $this->actingAs($this->admin, 'web');

        $oldLogo = 'customers/logos/old_logo.png';
        $oldQr = 'customers/qr_codes/old_qr.png';
        Storage::disk('public')->put($oldLogo, 'old-logo');
        Storage::disk('public')->put($oldQr, 'old-qr');

        $customer = PosCustomer::create([
            'name' => 'Tech Corp',
            'phone' => '0911223344',
            'customer_level' => 2,
            'logo_path' => $oldLogo,
            'qr_code_path' => $oldQr,
            'created_by' => $this->admin->id,
        ]);

        $newLogo = UploadedFile::fake()->image('brand_new_logo.png', 120, 120);
        $newQr = UploadedFile::fake()->image('brand_new_qr.png', 240, 240);

        Livewire::test(CustomerTable::class)
            ->call('editCustomer', $customer->id)
            ->set('editName', 'Tech Corp International')
            ->set('editLogo', $newLogo)
            ->set('editQrCode', $newQr)
            ->call('updateCustomer')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $customer->refresh();
        $this->assertSame('Tech Corp International', $customer->name);
        $this->assertNotNull($customer->logo_path);
        $this->assertNotNull($customer->qr_code_path);
        $this->assertNotSame($oldLogo, $customer->logo_path);
        $this->assertNotSame($oldQr, $customer->qr_code_path);

        Storage::disk('public')->assertMissing($oldLogo);
        Storage::disk('public')->assertMissing($oldQr);
        Storage::disk('public')->assertExists($customer->logo_path);
        Storage::disk('public')->assertExists($customer->qr_code_path);
    }

    public function test_customer_list_shows_logo_and_qr_columns(): void
    {
        $this->actingAs($this->admin, 'web');

        $customer = PosCustomer::create([
            'name' => 'Royal Garden Hotel',
            'phone' => '09555444333',
            'customer_level' => 3,
            'logo_path' => 'customers/logos/sample.png',
            'qr_code_path' => 'customers/qr_codes/sample_qr.png',
            'created_by' => $this->admin->id,
        ]);

        Livewire::test(CustomerTable::class)
            ->assertStatus(200)
            ->assertSee('Royal Garden Hotel')
            ->assertSee('View QR');
    }

    public function test_temporary_uploaded_file_creates_valid_stream(): void
    {
        $file = \Livewire\Features\SupportFileUploads\TemporaryUploadedFile::createTmpFile();
        $this->assertIsResource($file);

        $meta = stream_get_meta_data($file);
        $this->assertIsArray($meta);
        $this->assertArrayHasKey('uri', $meta);
        $this->assertFileExists($meta['uri']);
        fclose($file);
    }
}
