<?php

namespace Tests\Feature;

use App\Livewire\CustomerTable;
use App\Models\User;
use App\Modules\Customer\Models\PosCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerLivewireTest extends TestCase
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

    public function test_customer_table_renders(): void
    {
        $customer = PosCustomer::create([
            'name' => 'Aung Aung',
            'phone' => '09111222333',
            'customer_level' => 1,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'web');

        Livewire::test(CustomerTable::class)
            ->assertStatus(200)
            ->assertSee('Exhibitor')
            ->assertSee('Aung Aung')
            ->assertSee('Links')
            ->assertSee('/invitation/'.$customer->id.'/aung-aung')
            ->assertSee('Copy invitation link')
            ->assertDontSee('data-lucide="share-2"', false)
            ->assertDontSee('data-lucide="ticket"', false);
    }

    public function test_can_open_create_and_import_modals_via_exhibitor_events(): void
    {
        $this->actingAs($this->admin, 'web');

        Livewire::test(CustomerTable::class)
            ->dispatch('open-exhibitor-create')
            ->assertSet('createOpen', true)
            ->call('closeCreate')
            ->assertSet('createOpen', false)
            ->dispatch('open-exhibitor-import')
            ->assertSet('importOpen', true)
            ->call('closeImport')
            ->assertSet('importOpen', false);
    }

    public function test_customer_can_be_created_via_livewire(): void
    {
        $this->actingAs($this->admin, 'web');

        Livewire::test(CustomerTable::class)
            ->set('createName', 'Khin Khin')
            ->set('createPhone', '09555666777')
            ->set('createAddress', 'Yangon')
            ->set('createCustomerLevel', 2)
            ->call('createCustomer')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('pos_customers', [
            'name' => 'Khin Khin',
            'phone' => '09555666777',
        ]);
    }

    public function test_customer_can_be_edited_via_livewire(): void
    {
        $customer = PosCustomer::create([
            'name' => 'Ko Ko',
            'phone' => '09777888999',
            'customer_level' => 1,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'web');

        Livewire::test(CustomerTable::class)
            ->call('editCustomer', $customer->id)
            ->set('editName', 'Ko Ko Updated')
            ->call('updateCustomer')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('pos_customers', [
            'id' => $customer->id,
            'name' => 'Ko Ko Updated',
        ]);
    }

    public function test_edit_customer_populates_all_fields_and_cancels_cleanly(): void
    {
        $customer = PosCustomer::create([
            'name' => 'Daw Mya',
            'phone' => '09123123123',
            'address' => 'Mandalay, 78th St',
            'customer_level' => 3,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'web');

        Livewire::test(CustomerTable::class)
            ->call('editCustomer', $customer->id)
            ->assertSet('editingId', $customer->id)
            ->assertSet('editName', 'Daw Mya')
            ->assertSet('editPhone', '09123123123')
            ->assertSet('editAddress', 'Mandalay, 78th St')
            ->assertSet('editCustomerLevel', 3)
            ->call('closeEdit')
            ->assertSet('editingId', null);
    }

    public function test_customer_edit_validation_requires_name(): void
    {
        $customer = PosCustomer::create([
            'name' => 'Valid Name',
            'customer_level' => 1,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'web');

        Livewire::test(CustomerTable::class)
            ->call('editCustomer', $customer->id)
            ->set('editName', '')
            ->call('updateCustomer')
            ->assertHasErrors(['editName' => 'required'])
            ->assertSet('editingId', $customer->id);

        $customer->refresh();
        $this->assertSame('Valid Name', $customer->name);
    }

    public function test_customer_can_update_level_phone_and_address(): void
    {
        $customer = PosCustomer::create([
            'name' => 'U Ba',
            'phone' => '09111',
            'address' => 'Old Address',
            'customer_level' => 1,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'web');

        Livewire::test(CustomerTable::class)
            ->call('editCustomer', $customer->id)
            ->set('editName', 'U Ba Updated')
            ->set('editPhone', '09999888777')
            ->set('editAddress', 'New Address 123')
            ->set('editCustomerLevel', 3)
            ->call('updateCustomer')
            ->assertHasNoErrors()
            ->assertDispatched('toast')
            ->assertSet('editingId', null);

        $customer->refresh();
        $this->assertSame('U Ba Updated', $customer->name);
        $this->assertSame('09999888777', $customer->phone);
        $this->assertSame('New Address 123', $customer->address);
        $this->assertSame(3, $customer->customer_level);
    }

    public function test_customer_can_be_deleted_via_livewire(): void
    {
        $customer = PosCustomer::create([
            'name' => 'To Delete',
            'phone' => '09888999000',
            'customer_level' => 1,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'web');

        Livewire::test(CustomerTable::class)
            ->call('confirmDelete', $customer->id)
            ->call('deleteCustomer')
            ->assertDispatched('toast');

        $this->assertSoftDeleted('pos_customers', [
            'id' => $customer->id,
        ]);
    }
}
