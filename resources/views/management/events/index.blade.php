@extends('layouts.management')

@section('title', 'Event Details & Card Settings')

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb & Title Bar --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-400">
                <a href="{{ route('management.exhibitors.index') }}" class="hover:text-gray-600">Management</a>
                <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                <span class="text-[#465fff]">Event Details</span>
            </nav>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Event Details &amp; Invitation Card</h1>
            <p class="mt-0.5 text-sm text-gray-500">Configure official exhibition branding, dates, venue, and invitation card presentation.</p>
        </div>

        @if($sampleCustomer)
            <div class="flex items-center gap-3">
                <a href="{{ route('invitation.card', $sampleCustomer) }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-gray-900">
                    <i data-lucide="external-link" class="h-4 w-4 text-gray-500"></i>
                    <span>Preview Live Card</span>
                </a>
            </div>
        @endif
    </div>

    {{-- Summary Metric Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Exhibition</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600"><i data-lucide="gem" class="h-4 w-4"></i></span>
            </div>
            <p class="mt-2 text-lg font-bold text-gray-900 truncate">{{ $event->title }}</p>
            <p class="text-xs font-medium text-blue-600">{{ $event->edition }}</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Dates</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600"><i data-lucide="calendar" class="h-4 w-4"></i></span>
            </div>
            <p class="mt-2 text-base font-bold text-gray-900 truncate">{{ $event->date_display }}</p>
            <p class="text-xs font-medium text-amber-600">Official Schedule</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Venue</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600"><i data-lucide="map-pin" class="h-4 w-4"></i></span>
            </div>
            <p class="mt-2 text-base font-bold text-gray-900 truncate">{{ $event->venue }}</p>
            <p class="text-xs font-medium text-rose-600">Event Location</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Exhibitors</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"><i data-lucide="users" class="h-4 w-4"></i></span>
            </div>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $totalExhibitors }}</p>
            <p class="text-xs font-medium text-emerald-600">Registered Delegates</p>
        </div>
    </div>

    {{-- Main 2-Column Content Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">

        {{-- Left Column: Event Configuration Form (7 cols) --}}
        <div class="lg:col-span-7">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="border-b border-gray-100 pb-4">
                    <h2 class="text-lg font-bold text-gray-900">Official Exhibition Details</h2>
                    <p class="mt-0.5 text-xs text-gray-500">Update event information. Changes are instantly reflected across all invitation cards.</p>
                </div>

                <form method="POST" action="{{ route('management.events.update') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Event Title *</label>
                            <input type="text" name="title" value="{{ old('title', $event->title) }}" required class="mt-1 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#465fff] focus:ring-1 focus:ring-[#465fff]">
                            @error('title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Subtitle</label>
                            <input type="text" name="subtitle" value="{{ old('subtitle', $event->subtitle) }}" class="mt-1 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#465fff] focus:ring-1 focus:ring-[#465fff]">
                            @error('subtitle') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Edition *</label>
                            <input type="text" name="edition" value="{{ old('edition', $event->edition) }}" required placeholder="e.g. 4th Edition" class="mt-1 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#465fff] focus:ring-1 focus:ring-[#465fff]">
                            @error('edition') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Display Dates *</label>
                            <input type="text" name="date_display" value="{{ old('date_display', $event->date_display) }}" required placeholder="e.g. JANUARY 22 - 24 , 2027" class="mt-1 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#465fff] focus:ring-1 focus:ring-[#465fff]">
                            @error('date_display') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Venue / Location *</label>
                        <input type="text" name="venue" value="{{ old('venue', $event->venue) }}" required placeholder="e.g. Lotte Hotel Yangon Level 2" class="mt-1 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#465fff] focus:ring-1 focus:ring-[#465fff]">
                        @error('venue') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Invitation Honor Text</label>
                        <input type="text" name="invitation_text" value="{{ old('invitation_text', $event->invitation_text) }}" placeholder="e.g. Cordially Requests The Honor Of Your Presence" class="mt-1 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#465fff] focus:ring-1 focus:ring-[#465fff]">
                        @error('invitation_text') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Organizer Contact Phone</label>
                            <input type="text" name="contact_phone" value="{{ old('contact_phone', $event->contact_phone) }}" placeholder="e.g. 09450011223" class="mt-1 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#465fff] focus:ring-1 focus:ring-[#465fff]">
                            @error('contact_phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Organizer Email</label>
                            <input type="email" name="contact_email" value="{{ old('contact_email', $event->contact_email) }}" placeholder="e.g. info@timelessasia.com" class="mt-1 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#465fff] focus:ring-1 focus:ring-[#465fff]">
                            @error('contact_email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Card Footer Note</label>
                        <input type="text" name="footer_text" value="{{ old('footer_text', $event->footer_text) }}" placeholder="e.g. Timeless Asia • 4th Edition 2027" class="mt-1 w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#465fff] focus:ring-1 focus:ring-[#465fff]">
                        @error('footer_text') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Image Upload --}}
                    <div class="pt-3 border-t border-gray-100">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Event Logo (Card Header)</label>
                            <div class="mt-2 flex items-center gap-4">
                                <div class="h-14 w-28 rounded-xl bg-slate-900 p-2 border border-slate-700 flex items-center justify-center">
                                    <img src="{{ $event->logo_url }}" alt="Event Logo" class="h-full w-auto object-contain">
                                </div>
                                <div class="flex-1">
                                    <input type="file" name="logo" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                                    <p class="mt-1 text-[11px] text-gray-400">PNG, SVG or WEBP with transparent background recommended (Max 5MB).</p>
                                </div>
                            </div>
                            @error('logo') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex items-center justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-[#465fff] px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#3d54e6] focus:outline-none focus:ring-2 focus:ring-[#465fff] focus:ring-offset-2">
                            <i data-lucide="save" class="h-4 w-4"></i>
                            <span>Save Event Details</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Right Column: Live Card Preview & Details (5 cols) --}}
        <div class="lg:col-span-5 space-y-6">

            {{-- Miniature Live Invitation Card Preview --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Card Layout Preview</h3>
                        <p class="text-xs text-gray-400">Current visual design for exhibitors</p>
                    </div>
                    @if($sampleCustomer)
                        <a href="{{ route('invitation.card', $sampleCustomer) }}" target="_blank" class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-600 hover:bg-blue-100 transition">
                            <span>Open Card</span>
                            <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                        </a>
                    @endif
                </div>

                {{-- Mini Render of Card --}}
                <div class="rounded-2xl border-2 border-amber-400/40 bg-gradient-to-b from-[#081f4a] via-[#051532] to-[#020a18] p-4 text-center text-amber-50 shadow-xl">
                    <div class="rounded-xl border border-amber-300/30 p-4">
                        <img src="{{ $event->logo_url }}" alt="Logo" class="mx-auto h-12 w-auto object-contain">
                        <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-white/90">{{ $event->edition }}</p>
                        
                        <div class="mt-2 inline-flex items-center rounded-full border border-amber-300/40 bg-[#030d22]/80 px-2.5 py-0.5 text-[9px] font-bold tracking-widest text-amber-200 uppercase">
                            {{ $event->date_display }}
                        </div>

                        <p class="mt-1 text-[9px] font-medium tracking-wide text-amber-100/70">{{ $event->venue }}</p>

                        <div class="my-2.5 h-px w-16 mx-auto bg-amber-300/30"></div>

                        <p class="text-[9px] tracking-wider text-amber-200/80 uppercase">{{ $event->invitation_text }}</p>
                        
                        <p class="mt-1.5 text-base font-extrabold uppercase tracking-wide text-amber-100">
                            {{ $sampleCustomer?->name ?? 'Exhibitor Name' }}
                        </p>

                        <div class="mt-1.5 inline-block rounded-full border border-amber-300/40 bg-amber-400/10 px-2 py-0.5 text-[9px] font-bold tracking-wider text-amber-200 uppercase">
                            VIP EXHIBITOR
                        </div>

                        <div class="mt-3 flex justify-center">
                            <div class="rounded-lg border border-amber-300/50 bg-white p-1.5 shadow-md">
                                <div class="h-14 w-14 bg-slate-900 rounded flex items-center justify-center text-[8px] text-white font-mono">
                                    [ QR CODE ]
                                </div>
                            </div>
                        </div>

                        <p class="mt-2 text-[8px] tracking-widest text-amber-300/60 uppercase">
                            {{ $event->footer_text }}
                        </p>
                    </div>
                </div>

                {{-- Card Settings Summary --}}
                <div class="mt-4 space-y-2 border-t border-gray-100 pt-3 text-xs text-gray-600">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-gray-500"><i data-lucide="check-circle-2" class="h-3.5 w-3.5 text-emerald-500"></i> QR Code Scanning</span>
                        <span class="font-medium text-emerald-600">Active</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-gray-500"><i data-lucide="minus-circle" class="h-3.5 w-3.5 text-gray-400"></i> Barcode Graphic</span>
                        <span class="font-medium text-gray-400">Disabled</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-gray-500"><i data-lucide="minus-circle" class="h-3.5 w-3.5 text-gray-400"></i> Pass ID &amp; Location</span>
                        <span class="font-medium text-gray-400">Disabled</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-gray-500"><i data-lucide="palette" class="h-3.5 w-3.5 text-indigo-500"></i> Color Theme</span>
                        <span class="font-medium text-indigo-600">Royal Sapphire &amp; Gold</span>
                    </div>
                </div>

            </div>

        </div>

    </div>

</div>
@endsection
