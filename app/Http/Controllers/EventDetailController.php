<?php

namespace App\Http\Controllers;

use App\Models\EventDetail;
use App\Modules\Customer\Models\PosCustomer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventDetailController extends Controller
{
    public function index(): View
    {
        $event = EventDetail::current();
        $sampleCustomer = PosCustomer::query()->first();
        $totalExhibitors = PosCustomer::query()->count();

        return view('management.events.index', compact('event', 'sampleCustomer', 'totalExhibitors'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'edition' => ['nullable', 'string', 'max:255'],
            'date_display' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'venue' => ['required', 'string', 'max:255'],
            'invitation_text' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:100'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:5120'],
            'banner' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
        ]);

        $event = EventDetail::first() ?? new EventDetail();

        $event->title = $validated['title'];
        $event->subtitle = $validated['subtitle'] ?? 'Gems & Jewelry Exhibition';
        $event->edition = $validated['edition'] ?? '4th Edition';
        $event->date_display = $validated['date_display'];
        $event->start_date = $validated['start_date'] ?? null;
        $event->end_date = $validated['end_date'] ?? null;
        $event->venue = $validated['venue'];
        $event->invitation_text = $validated['invitation_text'] ?? 'Cordially Requests The Honor Of Your Presence';
        $event->contact_phone = $validated['contact_phone'] ?? null;
        $event->contact_email = $validated['contact_email'] ?? null;
        $event->footer_text = $validated['footer_text'] ?? 'Timeless Asia • 4th Edition 2027';

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('events/logos', 'public');
            $event->logo_path = $path;
        }

        if ($request->hasFile('banner')) {
            $path = $request->file('banner')->store('events/banners', 'public');
            $event->banner_path = $path;
        }

        $event->save();

        return redirect()->route('management.events.index')
            ->with('status', 'Event details and invitation card settings updated successfully.');
    }
}
