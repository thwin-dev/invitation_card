@extends('layouts.management')

@section('title', 'Exhibitor details')

@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div class="flex items-center gap-4">
        @if($customer->logo_url)
            <img src="{{ $customer->logo_url }}" alt="{{ $customer->name }}" class="h-16 w-16 rounded-2xl object-cover border border-gray-200 shadow-sm">
        @else
            <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 text-xl font-bold text-brand-600 border border-brand-100">
                {{ strtoupper(substr($customer->name, 0, 1)) }}
            </span>
        @endif
        <div>
            <a href="{{ route('management.exhibitors.index') }}" class="text-sm font-medium text-brand-500 hover:underline">&larr; Back to exhibitors</a>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-gray-900">{{ $customer->name }}</h1>
            <p class="text-sm text-gray-500">{{ $customer->phone ?: 'No phone provided' }}</p>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <button type="button" onclick="window.copyToClipboard('{{ $customer->invitation_url }}').then(() => window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Invitation link copied to clipboard!' } })))" class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 transition cursor-pointer">
            <i data-lucide="share-2" class="h-4 w-4 text-indigo-600"></i>
            <span>Copy Invitation Link</span>
        </button>
        <a href="{{ $customer->invitation_url }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition">
            <i data-lucide="ticket" class="h-4 w-4"></i>
            <span>View Invitation Card</span>
        </a>
    </div>
</div>

<div class="grid gap-6 md:grid-cols-2">
    {{-- Contact Information --}}
    <div class="pos-panel p-6">
        <h2 class="text-base font-semibold text-gray-900 border-b border-gray-100 pb-3 flex items-center gap-2">
            <i data-lucide="user" class="h-4 w-4 text-brand-600"></i>
            Contact Information
        </h2>
        <dl class="mt-4 space-y-4 text-sm">
            <div>
                <dt class="font-medium text-gray-500">Full Name</dt>
                <dd class="mt-1 text-gray-900 font-semibold">{{ $customer->name }}</dd>
            </div>
            <div>
                <dt class="font-medium text-gray-500">Phone Number</dt>
                <dd class="mt-1 text-gray-900 font-mono">{{ $customer->phone ?: 'Not specified' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-gray-500">Address</dt>
                <dd class="mt-1 text-gray-900">{{ $customer->address ?: 'Not specified' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-gray-500">Exhibitor Level</dt>
                <dd class="mt-1">
                    @if($customer->customer_level == 3)
                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 border border-amber-200">Level 3 (VIP)</span>
                    @elseif($customer->customer_level == 2)
                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 border border-blue-200">Level 2 (Silver)</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">Level 1 (Standard)</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="font-medium text-gray-500">Registered Date</dt>
                <dd class="mt-1 text-gray-900">{{ $customer->created_at?->format('F d, Y h:i A') ?: 'Unknown' }}</dd>
            </div>
        </dl>
    </div>

    {{-- Media & Attachments --}}
    <div class="pos-panel p-6 flex flex-col justify-between">
        <div>
            <h2 class="text-base font-semibold text-gray-900 border-b border-gray-100 pb-3 flex items-center gap-2">
                <i data-lucide="paperclip" class="h-4 w-4 text-brand-600"></i>
                Attachments
            </h2>

            <div class="mt-5 grid grid-cols-2 gap-4">
                {{-- Logo Attachment --}}
                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 text-center">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-3">Exhibitor Logo</p>
                    @if($customer->logo_url)
                        <div class="flex flex-col items-center gap-3">
                            <img src="{{ $customer->logo_url }}" alt="Logo" class="h-28 w-28 rounded-xl object-contain bg-white border border-gray-200 p-1 shadow-xs">
                            <a href="{{ $customer->logo_url }}" target="_blank" download class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 hover:text-brand-700">
                                <i data-lucide="download" class="h-3.5 w-3.5"></i> Download
                            </a>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-6 text-gray-400">
                            <i data-lucide="image-off" class="h-10 w-10 stroke-1 opacity-50 mb-2"></i>
                            <span class="text-xs">No logo uploaded</span>
                        </div>
                    @endif
                </div>

                {{-- QR Attachment --}}
                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 text-center">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-3">Exhibitor QR Attachment</p>
                    @if($customer->qr_code_url)
                        <div class="flex flex-col items-center gap-3">
                            <img src="{{ $customer->qr_code_url }}" alt="QR Code" class="h-28 w-28 rounded-xl object-contain bg-white border border-gray-200 p-1 shadow-xs">
                            <a href="{{ $customer->qr_code_url }}" target="_blank" download class="inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 hover:text-brand-700">
                                <i data-lucide="download" class="h-3.5 w-3.5"></i> Download
                            </a>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-6 text-gray-400">
                            <i data-lucide="qr-code" class="h-10 w-10 stroke-1 opacity-50 mb-2"></i>
                            <span class="text-xs">No QR attached</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
