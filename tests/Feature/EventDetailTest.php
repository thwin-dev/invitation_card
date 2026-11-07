<?php

namespace Tests\Feature;

use App\Models\EventDetail;
use App\Models\User;
use App\Modules\Customer\Models\PosCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'prohibition_flag' => false,
        ]);
    }

    public function test_guest_is_redirected_from_event_details_page(): void
    {
        $response = $this->get(route('management.events.index'));
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_event_details_page(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get(route('management.events.index'));

        $response->assertStatus(200);
        $response->assertSee('Event Details &amp; Invitation Card', false);
        $response->assertSee('Timeless Asia');
        $response->assertSee('4th Edition');
        $response->assertSee('JANUARY 22 - 24 , 2027');
        $response->assertSee('Lotte Hotel Yangon Level 2');
        $response->assertSee('Save Event Details');
        $response->assertDontSee('Facebook Cover');
        $response->assertDontSee('Promotional Banner');
    }

    public function test_authenticated_user_can_update_event_details(): void
    {
        Storage::fake('public');

        $logoFile = UploadedFile::fake()->image('new_event_logo.png', 400, 200);

        $response = $this->actingAs($this->admin, 'web')->put(route('management.events.update'), [
            'title' => 'Timeless Asia Grand Gala',
            'subtitle' => 'International Gems & Jewelry',
            'edition' => '5th Edition',
            'date_display' => 'FEBRUARY 10 - 14 , 2027',
            'venue' => 'Yangon Convention Center',
            'invitation_text' => 'Warmly Welcomes You To',
            'contact_phone' => '0999988877',
            'contact_email' => 'info@timelessasia.com',
            'footer_text' => 'Timeless Asia Grand Gala • 2027',
            'logo' => $logoFile,
        ]);

        $response->assertRedirect(route('management.events.index'));
        $response->assertSessionHas('status', 'Event details and invitation card settings updated successfully.');

        $event = EventDetail::first();
        $this->assertNotNull($event);
        $this->assertSame('Timeless Asia Grand Gala', $event->title);
        $this->assertSame('5th Edition', $event->edition);
        $this->assertSame('FEBRUARY 10 - 14 , 2027', $event->date_display);
        $this->assertSame('Yangon Convention Center', $event->venue);
        $this->assertSame('0999988877', $event->contact_phone);

        Storage::disk('public')->assertExists($event->logo_path);
    }

    public function test_invitation_card_dynamically_renders_updated_event_details(): void
    {
        $exhibitor = PosCustomer::create([
            'name' => 'Royal Sapphire Co.',
            'phone' => '0911223344',
            'address' => 'Hall 1',
            'customer_level' => 3,
            'created_by' => $this->admin->id,
        ]);

        EventDetail::query()->updateOrCreate(['id' => 1], [
            'title' => 'Timeless Asia Luxe',
            'subtitle' => 'Fine Gems & Diamonds',
            'edition' => 'Signature Edition',
            'date_display' => 'MARCH 1 - 5 , 2027',
            'venue' => 'Grand Palace Ballroom',
            'invitation_text' => 'Honors Your Distinguished Presence',
            'footer_text' => 'Timeless Asia Luxe • Signature 2027',
            'contact_phone' => '0955566677',
        ]);

        $response = $this->get(route('invitation.card', $exhibitor));
        $response->assertStatus(200);
        $response->assertSee('Signature Edition');
        $response->assertSee('MARCH 1 - 5 , 2027');
        $response->assertSee('Grand Palace Ballroom');
        $response->assertSee('Honors Your Distinguished Presence');
        $response->assertSee('Timeless Asia Luxe • Signature 2027');
    }
}
