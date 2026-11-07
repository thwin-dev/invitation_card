<?php

namespace Tests\Feature\POS;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkflowNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_login_and_locale_can_be_changed(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/localization/my')->assertRedirect();
        $this->assertSame('my', session('locale'));
    }

    public function test_authenticated_user_can_open_print_preview_routes(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123'), 'user_code' => 'TEST-001', 'prohibition_flag' => false]);
        $this->actingAs($user, 'web')->get('/management')->assertOk();
        $this->actingAs($user, 'web')->get('/management/sales/999/print')->assertNotFound();
        $this->actingAs($user, 'web')->get('/management/orders/999/print')->assertNotFound();
    }
}
