<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $customer->name }} - Timeless Asia Gems &amp; Jewelry Exhibition</title>
    <link rel="icon" type="image/png" href="{{ asset('images/timeless-asia-logo.png') }}">

    <!-- Google Fonts for Luxury Gems & Jewelry Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700;800;900&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        .font-cinzel { font-family: 'Cinzel', serif; }
        .font-cormorant { font-family: 'Cormorant Garamond', Georgia, serif; }
        .font-montserrat { font-family: 'Montserrat', sans-serif; }

        /* Metallic gold gradient text */
        .text-gold-gradient {
            background: linear-gradient(135deg, #fef3c7 0%, #f7e096 30%, #ffd778 50%, #d4a742 80%, #a17822 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .gold-border-glow {
            box-shadow: 0 0 50px -10px rgba(245, 158, 11, 0.28), 0 0 80px -20px rgba(37, 99, 235, 0.35);
        }

        @media print {
            @page {
                size: A5 portrait;
                margin: 0;
            }
            body {
                background: #051532 !important;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }
            .print-card-wrapper {
                min-height: 100vh !important;
                padding: 1cm !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                background: transparent !important;
            }
            .invitation-card {
                box-shadow: none !important;
                page-break-inside: avoid !important;
            }
        }
    </style>
</head>
<body class="min-h-full bg-gradient-to-b from-[#020a1a] via-[#051532] to-[#010610] text-amber-50 flex flex-col justify-center items-center p-3 sm:p-6 lg:p-8 antialiased selection:bg-[#d4a742] selection:text-black">

    @php
        $event = \App\Models\EventDetail::current();
        $tier = (int) $customer->customer_level;
        if ($tier === 3) {
            $tierTitle = 'VIP EXHIBITOR';
            $tierPillStyle = 'border-amber-300/50 bg-amber-400/15 text-amber-100';
        } elseif ($tier === 2) {
            $tierTitle = 'SILVER EXHIBITOR';
            $tierPillStyle = 'border-slate-300/50 bg-slate-300/15 text-slate-100';
        } else {
            $tierTitle = 'OFFICIAL EXHIBITOR';
            $tierPillStyle = 'border-amber-300/35 bg-amber-500/10 text-amber-200';
        }
    @endphp

    {{-- Main Printable Invitation Card Container (Exclusively the Card, No Buttons) --}}
    <div class="print-card-wrapper w-full flex justify-center items-center my-auto py-4">
        <article class="invitation-card relative w-full max-w-md rounded-3xl border-2 border-amber-400/40 bg-gradient-to-b from-[#081f4a]/95 via-[#051532]/95 to-[#020a18]/95 p-2 sm:p-3.5 backdrop-blur-2xl transition-all duration-300 gold-border-glow overflow-hidden">

            {{-- Ambient Sapphire and Amber Jewel Glows --}}
            <div class="pointer-events-none absolute -top-24 -left-24 h-64 w-64 rounded-full bg-blue-500/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -right-24 h-64 w-64 rounded-full bg-amber-500/15 blur-3xl"></div>

            {{-- Inner Elegant Double Hairline Gold Border with Corner Ornaments --}}
            <div class="relative rounded-[22px] border border-amber-300/30 p-5 sm:p-7 flex flex-col items-center text-center">

                {{-- Corner Gemstone Accents --}}
                <div class="pointer-events-none absolute top-2.5 left-2.5 text-amber-300/50 text-xs">✦</div>
                <div class="pointer-events-none absolute top-2.5 right-2.5 text-amber-300/50 text-xs">✦</div>
                <div class="pointer-events-none absolute bottom-2.5 left-2.5 text-amber-300/50 text-xs">✦</div>
                <div class="pointer-events-none absolute bottom-2.5 right-2.5 text-amber-300/50 text-xs">✦</div>

                {{-- Official Exhibition Logo (Enlarged per reference) --}}
                <div class="mt-2 mb-1 flex flex-col items-center justify-center">
                    <img src="{{ $event->logo_url }}" 
                         alt="{{ $event->title }} - {{ $event->subtitle }}" 
                         crossorigin="anonymous"
                         class="h-20 sm:h-24 w-auto max-w-[300px] sm:max-w-[340px] object-contain drop-shadow-[0_4px_18px_rgba(255,255,255,0.22)]">
                    <p class="mt-1 font-cinzel text-xs sm:text-sm font-semibold tracking-[0.3em] text-white/95 uppercase">
                        {{ $event->edition }}
                    </p>
                </div>

                {{-- Exhibition Date Badge (from Official Facebook Cover) --}}
                <div class="mt-3 inline-flex items-center justify-center rounded-full border border-amber-300/40 bg-[#030d22]/70 px-4 py-1.5 backdrop-blur-sm shadow-[0_0_15px_rgba(245,158,11,0.15)]">
                    <span class="font-cinzel text-[11px] sm:text-xs font-bold tracking-[0.24em] text-amber-200 uppercase">
                        {{ $event->date_display }}
                    </span>
                </div>

                {{-- Official Venue --}}
                <p class="mt-2 font-montserrat text-[11px] font-medium tracking-wider text-amber-100/85 flex items-center justify-center gap-1.5">
                    <i data-lucide="map-pin" class="h-3 w-3 text-amber-400"></i>
                    <span>{{ $event->venue }}</span>
                </p>

                {{-- Fine Ornamental Divider --}}
                <div class="flex items-center justify-center gap-2.5 my-3.5 text-amber-300/70">
                    <span class="h-px w-10 sm:w-14 bg-gradient-to-r from-transparent to-amber-300/60"></span>
                    <span class="text-xs">✦</span>
                    <svg class="w-4 h-4 text-amber-300" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2L14.4 8.6L21.5 9.2L16.2 13.9L17.8 21L12 17.3L6.2 21L7.8 13.9L2.5 9.2L9.6 8.6L12 2Z"/>
                    </svg>
                    <span class="text-xs">✦</span>
                    <span class="h-px w-10 sm:w-14 bg-gradient-to-l from-transparent to-amber-300/60"></span>
                </div>

                {{-- Formal Invitation Honor Text --}}
                <p class="font-cinzel text-[10px] sm:text-[11px] font-medium tracking-[0.26em] text-amber-200/80 uppercase">
                    {{ $event->invitation_text }}
                </p>

                {{-- Optional Exhibitor Logo Medallion (if uploaded) --}}
                @if($customer->logo_url)
                    <div class="mt-3.5 mb-1 flex items-center justify-center">
                        <div class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl border border-amber-300/40 bg-slate-950/70 p-2 shadow-lg backdrop-blur flex items-center justify-center">
                            <img src="{{ $customer->logo_url }}" alt="{{ $customer->name }}" crossorigin="anonymous" class="h-full w-full object-contain rounded-xl">
                        </div>
                    </div>
                @endif

                {{-- Exhibitor Name in Regal Gold Typography --}}
                <h1 class="mt-3 text-2xl sm:text-3xl font-extrabold tracking-wide text-gold-gradient font-cinzel leading-snug uppercase px-2">
                    {{ $customer->name }}
                </h1>

                {{-- Tier Badge --}}
                <div class="mt-2.5 inline-flex items-center gap-2 rounded-full border {{ $tierPillStyle }} px-4 py-1 backdrop-blur-sm">
                    <span class="text-amber-300 text-[10px]">✦</span>
                    <span class="font-cinzel text-[11px] font-bold tracking-[0.2em] uppercase">
                        {{ $tierTitle }}
                    </span>
                    <span class="text-amber-300 text-[10px]">✦</span>
                </div>

                {{-- Subtle Divider --}}
                <div class="w-20 h-px bg-gradient-to-r from-transparent via-amber-300/40 to-transparent my-3.5"></div>

                {{-- Compact QR Code Section (Scaled down for balance) --}}
                <div class="flex flex-col items-center">
                    <div class="relative rounded-xl border-2 border-amber-300/60 bg-white p-2 sm:p-2.5 shadow-[0_4px_20px_rgba(245,158,11,0.2)]">
                        @if($customer->qr_code_url)
                            <img src="{{ $customer->qr_code_url }}" alt="Invitation QR Code" crossorigin="anonymous" class="h-24 w-24 sm:h-28 sm:w-28 object-contain rounded-md">
                        @else
                            {{-- Clean Fallback QR Graphic --}}
                            <svg class="h-24 w-24 sm:h-28 sm:w-28 text-slate-900" viewBox="0 0 100 100" fill="currentColor">
                                <rect x="5" y="5" width="28" height="28" rx="4" fill="currentColor"/>
                                <rect x="9" y="9" width="20" height="20" rx="2" fill="#ffffff"/>
                                <rect x="13" y="13" width="12" height="12" rx="1" fill="currentColor"/>

                                <rect x="67" y="5" width="28" height="28" rx="4" fill="currentColor"/>
                                <rect x="71" y="9" width="20" height="20" rx="2" fill="#ffffff"/>
                                <rect x="75" y="13" width="12" height="12" rx="1" fill="currentColor"/>

                                <rect x="5" y="67" width="28" height="28" rx="4" fill="currentColor"/>
                                <rect x="9" y="71" width="20" height="20" rx="2" fill="#ffffff"/>
                                <rect x="13" y="75" width="12" height="12" rx="1" fill="currentColor"/>

                                <rect x="38" y="8" width="6" height="6"/>
                                <rect x="48" y="14" width="6" height="6"/>
                                <rect x="40" y="24" width="8" height="6"/>
                                <rect x="52" y="26" width="6" height="8"/>

                                <rect x="8" y="38" width="6" height="6"/>
                                <rect x="18" y="46" width="8" height="6"/>
                                <rect x="26" y="38" width="6" height="8"/>

                                <rect x="38" y="38" width="8" height="8" rx="1"/>
                                <rect x="50" y="42" width="6" height="6"/>
                                <rect x="42" y="52" width="10" height="6"/>
                                <rect x="58" y="50" width="6" height="8"/>

                                <rect x="68" y="38" width="8" height="6"/>
                                <rect x="82" y="44" width="10" height="6"/>
                                <rect x="76" y="54" width="8" height="8"/>

                                <rect x="38" y="68" width="6" height="8"/>
                                <rect x="48" y="76" width="8" height="6"/>
                                <rect x="40" y="86" width="6" height="8"/>
                                <rect x="52" y="84" width="8" height="6"/>

                                <rect x="68" y="68" width="8" height="6"/>
                                <rect x="80" y="72" width="6" height="8"/>
                                <rect x="74" y="82" width="8" height="8"/>
                                <rect x="88" y="86" width="6" height="6"/>
                            </svg>
                        @endif
                    </div>

                    <p class="mt-2 font-cinzel text-[9px] sm:text-[10px] font-semibold tracking-[0.22em] text-amber-200/80 uppercase">
                        Exclusive Access QR Code
                    </p>
                </div>

                {{-- Elegant Footer --}}
                <div class="mt-4 pt-3 border-t border-amber-300/20 w-full flex flex-col items-center">
                    <p class="font-cinzel text-[9px] tracking-[0.28em] text-amber-300/60 uppercase">
                        {{ $event->footer_text ?: ($event->title . ' • ' . $event->edition . ' 2027') }}
                    </p>
                </div>

            </div>
        </article>
    </div>

    {{-- Download Button (Hidden in Print & Export) --}}
    <div class="no-print my-6 flex items-center justify-center">
        <button id="downloadBtn" type="button" onclick="downloadInvitationCard()" class="inline-flex items-center gap-2.5 rounded-full border border-amber-300/60 bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-slate-950 shadow-[0_4px_25px_rgba(245,158,11,0.35)] transition-all duration-300 hover:scale-105 active:scale-95 cursor-pointer">
            <svg class="h-4 w-4 text-slate-950" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <span id="downloadBtnText">Download Card (PNG)</span>
        </button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.lucide?.createIcons();
        });
    </script>
    <script type="module">
        import { domToPng } from 'https://cdn.jsdelivr.net/npm/modern-screenshot@4.4.39/+esm';

        window.downloadInvitationCard = async function() {
            var card = document.querySelector('.invitation-card');
            if (!card) return;

            var btn = document.getElementById('downloadBtn');
            var btnText = document.getElementById('downloadBtnText');
            var originalText = btnText ? btnText.innerText : 'Download Card (PNG)';
            if (btnText) btnText.innerText = 'Generating Card...';
            if (btn) btn.disabled = true;

            try {
                var dataUrl = await domToPng(card, {
                    scale: 2,
                    backgroundColor: '#051532',
                    quality: 1,
                });
                var link = document.createElement('a');
                link.download = '{{ $customer->slug }}-invitation-card.png';
                link.href = dataUrl;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch(err) {
                console.error('Download card error:', err);
                alert('Failed to generate card image: ' + err.message);
            } finally {
                if (btnText) btnText.innerText = originalText;
                if (btn) btn.disabled = false;
            }
        };
    </script>
</body>
</html>
