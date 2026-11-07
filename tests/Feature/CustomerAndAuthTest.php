<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Customer\Models\PosCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAndAuthTest extends TestCase
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

    public function test_login_screen_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Sign in to your account');
    }

    public function test_user_can_authenticate_and_redirects_to_customers(): void
    {
        $response = $this->post('/login', [
            'user_code' => 'ADMIN-001',
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('management.customers.index'));
        $this->assertAuthenticatedAs($this->admin, 'web');
    }

    public function test_authenticated_user_can_access_customers_index(): void
    {
        PosCustomer::create([
            'name' => 'Test Customer',
            'phone' => '091234567',
            'address' => 'Yangon',
            'customer_level' => 1,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin, 'web')->get(route('management.customers.index'));

        $response->assertStatus(200);
        $response->assertSee('Exhibitors');
        $response->assertSee('Test Customer');
    }

    public function test_user_can_create_customer(): void
    {
        $response = $this->actingAs($this->admin, 'web')->post(route('management.customers.store'), [
            'name' => 'New Customer',
            'phone' => '09999888777',
            'address' => 'Mandalay',
            'customer_level' => 2,
        ]);

        $response->assertRedirect(route('management.customers.index'));
        $this->assertDatabaseHas('pos_customers', [
            'name' => 'New Customer',
            'phone' => '09999888777',
            'customer_level' => 2,
        ]);
    }

    public function test_authenticated_user_can_access_exhibitors_index_and_show(): void
    {
        $exhibitor = PosCustomer::create([
            'name' => 'Grand Pavilion Exhibitor',
            'phone' => '0912399999',
            'address' => 'Hall A, Booth 12',
            'customer_level' => 3,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin, 'web')->get(route('management.exhibitors.index'));
        $response->assertStatus(200);
        $response->assertSee('Exhibitors');
        $response->assertSee('Grand Pavilion Exhibitor');

        $showResponse = $this->actingAs($this->admin, 'web')->get(route('management.exhibitors.show', $exhibitor));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Exhibitor details');
        $showResponse->assertSee('Hall A, Booth 12');
        $showResponse->assertSee('Exhibitor Level');
        $showResponse->assertSee('View Invitation Card');
    }

    public function test_authenticated_user_can_access_invitation_card_page(): void
    {
        $exhibitor = PosCustomer::create([
            'name' => 'Elite Motors Corp',
            'phone' => '0988776655',
            'address' => 'Hall 4, Booth 88',
            'customer_level' => 3,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin, 'web')->get(route('management.exhibitors.card', $exhibitor));
        $response->assertStatus(200);
        $response->assertSee('Elite Motors Corp');
        $response->assertSee('VIP EXHIBITOR');
        $response->assertSee('timeless-asia-logo-white.png');
        $response->assertSee('Timeless Asia - Gems &amp; Jewelry Exhibition', false);
        // Verify simplified card: no location, no pass ID, no barcode, no buttons
        $response->assertDontSee('Hall 4, Booth 88');
        $response->assertDontSee('EXH-');
        $response->assertDontSee('Print / Save PDF');
        $response->assertDontSee('<button', false);
        $response->assertDontSee('sidebar-label');
    }

    public function test_public_user_can_access_direct_invitation_card(): void
    {
        $exhibitor = PosCustomer::create([
            'name' => 'Solar Energy Solutions',
            'phone' => '0977665544',
            'address' => 'Green Pavilion, Stand 5',
            'customer_level' => 1,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->get(route('invitation.card', $exhibitor));
        $response->assertStatus(200);
        $response->assertSee('Solar Energy Solutions');
        $response->assertSee('OFFICIAL EXHIBITOR');
        $response->assertSee('timeless-asia-logo-white.png');
        $response->assertSee('Timeless Asia - Gems &amp; Jewelry Exhibition', false);
        // Verify simplified card: no location, no pass ID, no barcode, no buttons
        $response->assertDontSee('Green Pavilion, Stand 5');
        $response->assertDontSee('EXH-');
        $response->assertDontSee('<button', false);
        $response->assertDontSee('sidebar-label');
    }

    public function test_user_can_logout(): void
    {
        $response = $this->actingAs($this->admin, 'web')->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest('web');
    }
}
