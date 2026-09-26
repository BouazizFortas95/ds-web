<!DOCTYPE html>
@php
    use Filament\Facades\Filament;

    $locale = app()->getLocale();
    $textDirection = $textDirection ?? config("localization.directions.{$locale}", 'ltr');

    // The dashboard is a Filament page, so the primary CTA links straight at the
    // panel rather than bouncing through the `/dashboard` redirect shim.
    $dashboardUrl = Filament::getUrl();
@endphp
<html lang="{{ $locale }}" dir="{{ $textDirection }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050505">
    <meta name="description" content="{{ __('site.welcome.meta_description') }}">
    <title>{{ __('site.welcome.title') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap');

        :root {
            color-scheme: dark;
            --void: #050505;
            --panel: #10141d;
            --border: #1e293b;
            --cyan: #00F0FF;
            --green: #39FF14;
            --ink: #E2E8F0;
        }

        body {
            background: var(--void);
            color: var(--ink);
            font-family: 'Cairo', 'Noto Sans Arabic', sans-serif;
        }

        .mono { font-family: 'JetBrains Mono', monospace; }

        .glass {
            background: linear-gradient(145deg, rgba(16,20,29,.94), rgba(8,12,20,.82));
            border: 1px solid var(--border);
            backdrop-filter: blur(18px);
        }

        .text-glow-cyan { text-shadow: 0 0 18px rgba(0,240,255,.55); }
        .text-glow-green { text-shadow: 0 0 18px rgba(57,255,20,.5); }

        .grid-bg {
            background-image:
                linear-gradient(rgba(0,240,255,.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,240,255,.035) 1px, transparent 1px);
            background-size: 44px 44px;
        }

        .noise {
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.035'/%3E%3C/svg%3E");
        }

        .scanline {
            position: absolute; inset: 0;
            background: repeating-linear-gradient(0deg, transparent, transparent 3px, rgba(0,240,255,.018) 4px);
            pointer-events: none;
        }

        .btn-primary, .btn-cyan, .btn-ghost {
            position: relative; isolation: isolate; overflow: hidden;
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease, color .22s ease, background-color .22s ease;
        }

        .btn-primary:hover, .btn-cyan:hover, .btn-ghost:hover,
        .btn-primary:focus-visible, .btn-cyan:focus-visible, .btn-ghost:focus-visible {
            transform: translateY(-2px);
        }

        .btn-primary:hover, .btn-primary:focus-visible { box-shadow: 0 0 28px rgba(57,255,20,.4), inset 0 0 18px rgba(57,255,20,.12); }
        .btn-cyan:hover, .btn-cyan:focus-visible { box-shadow: 0 0 26px rgba(0,240,255,.32), inset 0 0 18px rgba(0,240,255,.1); }
        .btn-ghost:hover, .btn-ghost:focus-visible { border-color: rgba(226,232,240,.4); background: rgba(226,232,240,.06); }
        .btn-primary:active, .btn-cyan:active, .btn-ghost:active { transform: translateY(0); }

        .btn-primary:focus-visible, .btn-cyan:focus-visible, .btn-ghost:focus-visible,
        a:focus-visible {
            outline: 2px solid var(--cyan);
            outline-offset: 3px;
        }

        .btn-primary::before, .btn-cyan::before, .btn-ghost::before {
            content: ''; position: absolute; inset: 0; z-index: -1;
            transform: translateX(-120%) skewX(-20deg);
            transition: transform .5s ease;
        }
        .btn-primary::before { background: linear-gradient(90deg, transparent, rgba(255,255,255,.3), transparent); }
        .btn-cyan::before { background: linear-gradient(90deg, transparent, rgba(0,240,255,.2), transparent); }
        .btn-primary:hover::before, .btn-primary:focus-visible::before,
        .btn-cyan:hover::before, .btn-cyan:focus-visible::before { transform: translateX(120%) skewX(-20deg); }

        .feature-card { transition: transform .3s ease, border-color .3s ease, box-shadow .3s ease; }
        @media (hover: none) {
            .feature-card:hover { transform: none; }
        }
        .feature-card:hover { transform: translateY(-6px); border-color: rgba(0,240,255,.45); box-shadow: 0 12px 40px rgba(0,0,0,.5), 0 0 30px rgba(0,240,255,.1); }

        .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }
        @keyframes pulse-dot { 0%,100% { opacity: 1; transform: scale(1); } 50% { opacity: .45; transform: scale(.82); } }

        .data-stream {
            stroke-dasharray: 6 100;
            animation: data-stream 2.5s linear infinite;
        }
        .data-stream-delayed { animation-delay: .8s; }
        @keyframes data-stream {
            from { stroke-dashoffset: 106; }
            to { stroke-dashoffset: 0; }
        }

        @media (prefers-reduced-motion: reduce) {
            html:focus-within { scroll-behavior: auto; }
            *, *::before, *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
            }
        }

        .feature-card:nth-child(2) { transition-delay: .06s; }
        .feature-card:nth-child(3) { transition-delay: .12s; }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden antialiased selection:bg-[#00F0FF]/25 selection:text-[#00F0FF]">
    <a href="#main-content" class="fixed start-4 top-4 z-[100] -translate-y-24 rounded-lg border border-[#00F0FF]/50 bg-[#10141d] px-4 py-2 font-semibold text-[#00F0FF] shadow-[0_0_24px_rgba(0,240,255,0.25)] transition focus:translate-y-0">
        {{ __('site.welcome.skip') }}
    </a>

    <!-- Ambient background layers -->
    <div class="pointer-events-none fixed inset-0 z-0 grid-bg" aria-hidden="true"></div>
    <div class="pointer-events-none fixed inset-0 z-0 noise" aria-hidden="true"></div>
    <div class="pointer-events-none fixed -top-40 right-1/4 h-96 w-96 rounded-full bg-[#00F0FF]/8 blur-[130px]" aria-hidden="true"></div>
    <div class="pointer-events-none fixed bottom-0 left-1/4 h-80 w-80 rounded-full bg-[#39FF14]/6 blur-[120px]" aria-hidden="true"></div>

    <div class="relative z-10 flex min-h-screen flex-col">
        <!-- ═══════════ NAVIGATION ═══════════ -->
        <header class="sticky top-0 z-50 border-b border-slate-800/60 bg-[#050505]/80 backdrop-blur-xl">
            <nav class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-4 gap-y-3 px-4 py-4 sm:px-6 lg:px-8" aria-label="{{ __('site.common.nav_aria') }}">
                <!-- Brand -->
                <a href="{{ route('home') }}" class="group flex shrink-0 items-center gap-3" aria-label="{{ __('site.common.home_aria') }}">
                    <div class="relative flex h-11 w-11 items-center justify-center rounded-xl border border-[#00F0FF]/30 bg-slate-900/90 shadow-[0_0_18px_rgba(0,240,255,0.18)] transition group-hover:border-[#00F0FF]/60 group-hover:shadow-[0_0_26px_rgba(0,240,255,0.3)]">
                        <span class="absolute inset-0 rounded-xl opacity-0 transition group-hover:opacity-100" style="box-shadow: inset 0 0 16px rgba(0,240,255,.16)"></span>
                        <svg viewBox="0 0 64 64" class="h-6 w-6" fill="none" aria-hidden="true">
                            <circle cx="32" cy="32" r="27" stroke="#00F0FF" stroke-width="2.4" opacity=".75"/>
                            <path d="M20 39L28 19L36 27L44 15L47 39H20Z" stroke="#39FF14" stroke-width="2.4" stroke-linejoin="round"/>
                            <path d="M24 44H40" stroke="#00F0FF" stroke-width="2.4" stroke-linecap="round"/>
                            <circle cx="32" cy="32" r="31" stroke="#00F0FF" stroke-width="1" stroke-dasharray="4 8" opacity=".35"/>
                        </svg>
                    </div>
                    <div class="leading-none">
                        <div class="mono text-[10px] tracking-[0.22em] text-[#00F0FF]/80 uppercase">{{ __('site.common.brand_name') }}</div>
                        <div class="mt-1 text-base font-extrabold tracking-wide text-slate-100">{{ __('site.common.brand_local_name') }}</div>
                    </div>
                </a>

                <!-- Desktop nav links -->
                <div class="hidden items-center gap-7 text-sm font-medium text-slate-400 lg:flex">
                    <a href="#solution" class="transition hover:text-[#00F0FF]">{{ __('site.common.solution') }}</a>
                    <a href="#features" class="transition hover:text-[#00F0FF]">{{ __('site.common.features') }}</a>
                    <a href="#compliance" class="transition hover:text-[#00F0FF]">{{ __('site.common.compliance') }}</a>
                </div>

                <!-- Language + actions -->
                <div class="ms-auto flex flex-wrap items-center justify-end gap-2 sm:gap-3 lg:ms-0">
                    <x-language-switcher />

                    {{-- A signed-in operator has no use for the sign-in or sign-up
                         links, and offering them invites a second account on a
                         console that is meant to have one operator. Conversely a
                         guest cannot reach the dashboard, so that button stays
                         out of their nav entirely. --}}
                    @guest
                        <a href="{{ route('login') }}" class="btn-ghost items-center justify-center rounded-lg border border-slate-700/80 bg-slate-900/30 px-2.5 py-2 text-xs font-semibold text-slate-300 sm:inline-flex sm:px-3.5 sm:text-sm">
                            <span>{{ __('site.common.login') }}</span>
                        </a>
                        <a href="{{ route('register') }}" class="btn-cyan inline-flex items-center justify-center rounded-lg border border-[#00F0FF]/50 bg-[#00F0FF]/8 px-2.5 py-2 text-xs font-semibold text-[#00F0FF] shadow-[0_0_16px_rgba(0,240,255,0.12)] sm:px-3.5 sm:text-sm">
                            <span>{{ __('site.common.signup') }}</span>
                        </a>
                    @endguest

                    @auth
                        <a href="{{ $dashboardUrl }}" class="btn-primary inline-flex items-center justify-center rounded-lg border border-[#39FF14]/60 bg-[#39FF14]/12 px-2.5 py-2 text-xs font-semibold text-[#d9ffdb] shadow-[0_0_20px_rgba(57,255,20,0.16)] sm:px-3.5 sm:text-sm">
                            <span>{{ __('site.common.dashboard') }}</span>
                            <span class="pulse-dot mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-[#39FF14]" aria-hidden="true"></span>
                        </a>
                    @endauth
                </div>
            </nav>
        </header>

        <!-- ═══════════ HERO ═══════════ -->
        <main id="main-content" class="flex-1">
            {{-- Consumes the flash the logout action sets. Without this the
                 message is written to the session and then dropped unread. --}}
            @if (session('status'))
                <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
                    <div role="status" class="rounded-2xl border border-[#39FF14]/40 bg-[#39FF14]/10 px-5 py-3 text-sm text-[#dfffe2]">
                        {{ session('status') }}
                    </div>
                </div>
            @endif

            <section class="relative overflow-hidden" aria-labelledby="hero-title">
                <!-- Decorative corner brackets -->
                <div class="pointer-events-none absolute right-8 top-16 h-20 w-20 border-r border-t border-[#00F0FF]/25" aria-hidden="true"></div>
                <div class="pointer-events-none absolute left-8 top-16 h-20 w-20 border-l border-t border-[#00F0FF]/25" aria-hidden="true"></div>

                <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
                    <div class="grid items-center gap-14 lg:grid-cols-[1.05fr_1fr] lg:gap-12">
                        <!-- Hero text -->
                        <div class="order-1 lg:order-1">
                            <div class="mb-6 inline-flex items-center gap-2.5 rounded-full border border-[#00F0FF]/25 bg-[#00F0FF]/6 px-4 py-1.5 text-xs font-semibold text-[#00F0FF] backdrop-blur">
                                <span class="pulse-dot h-1.5 w-1.5 rounded-full bg-[#00F0FF]" aria-hidden="true"></span>
                                <span class="mono technical tracking-[0.18em] uppercase">{{ __('site.welcome.badge') }}</span>
                                <span class="hidden sm:inline text-[#00F0FF]/40">|</span>
                                <span class="hidden sm:inline text-slate-400">{{ __('site.welcome.release', ['year' => date('Y')]) }}</span>
                            </div>

                            <h1 id="hero-title" class="text-4xl font-black leading-[1.25] tracking-tight text-slate-50 sm:text-5xl lg:text-[3.4rem]">
                                <span class="technical block text-slate-100">{{ __('site.welcome.hero_name') }}</span>
                                <span class="mono technical mt-3 block text-xl font-bold tracking-[0.06em] text-[#00F0FF] text-glow-cyan sm:text-2xl lg:text-[1.7rem]">{{ __('site.welcome.hero_local_name') }}</span>
                                <span class="technical mt-6 block text-2xl font-bold leading-relaxed text-slate-200 sm:text-3xl lg:text-[2rem]">{{ __('site.welcome.hero_suffix') }}</span>

                            </h1>

                            <p class="technical mt-7 max-w-xl text-lg leading-relaxed text-slate-300 sm:text-xl">
                                <span class="font-bold text-slate-100">{{ __('site.welcome.subtitle_zero') }}</span>
                                <span class="font-bold text-[#39FF14] text-glow-green"> {{ __('site.welcome.subtitle_sovereignty') }}</span>
                                <span class="font-semibold"> {{ __('site.welcome.subtitle_surveillance') }} </span>
                                <span class="mono font-bold text-[#00F0FF]" dir="ltr">RK3588</span>
                            </p>

                            <div class="mt-9 flex flex-col gap-3 sm:flex-row sm:items-center">
                                @guest
                                    <a href="{{ $dashboardUrl }}" class="btn-primary group inline-flex items-center justify-center gap-2.5 rounded-xl border border-[#39FF14]/70 bg-[#39FF14]/14 px-7 py-3.5 text-base font-bold text-[#ddffdf] shadow-[0_0_24px_rgba(57,255,20,0.2)] backdrop-blur sm:min-w-52">
                                        <svg viewBox="0 0 24 24" class="h-5 w-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path d="M3 3v7h7M21 21v-7h-7"/>
                                            <path d="M3.5 13a8.5 8.5 0 0 1 14-4M20.5 11a8.5 8.5 0 0 1-14 4"/>
                                        </svg>
                                        {{ __('site.common.access_dashboard') }}
                                        <span class="mono text-xs font-normal text-[#39FF14]/70">↗</span>
                                    </a>
                                    <a href="{{ route('register') }}" class="btn-cyan inline-flex items-center justify-center gap-2.5 rounded-xl border border-[#00F0FF]/60 bg-transparent px-7 py-3.5 text-base font-bold text-[#00F0FF] shadow-[0_0_20px_rgba(0,240,255,0.12)] sm:min-w-48">
                                        <span>{{ __('site.common.create_account') }}</span>
                                        <span class="mono text-xs text-[#00F0FF]/60">→</span>
                                    </a>
                                @endguest

                                {{-- Signed in, the hero's job is done: hand over the
                                     console instead of advertising a second way in. --}}
                                @auth
                                    <a href="{{ $dashboardUrl }}" class="btn-primary group inline-flex items-center justify-center gap-2.5 rounded-xl border border-[#39FF14]/70 bg-[#39FF14]/14 px-7 py-3.5 text-base font-bold text-[#ddffdf] shadow-[0_0_24px_rgba(57,255,20,0.2)] backdrop-blur sm:min-w-52">
                                        <svg viewBox="0 0 24 24" class="h-5 w-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path d="M3 3v7h7M21 21v-7h-7"/>
                                            <path d="M3.5 13a8.5 8.5 0 0 1 14-4M20.5 11a8.5 8.5 0 0 1-14 4"/>
                                        </svg>
                                        {{ __('site.common.dashboard') }}
                                        <span class="pulse-dot mr-2 inline-block h-2 w-2 rounded-full bg-[#39FF14]" aria-hidden="true"></span>
                                    </a>
                                @endauth
                            </div>

                            <div class="mt-8 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-500">
                                <span class="mono inline-flex items-center gap-1.5">
                                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5 text-[#39FF14]" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    {{ __('site.welcome.offline_first') }}
                                </span>
                                <span class="mono inline-flex items-center gap-1.5">
                                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5 text-[#39FF14]" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    {{ __('site.welcome.onnx_inference') }}
                                </span>
                                <span class="mono inline-flex items-center gap-1.5">
                                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5 text-[#39FF14]" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    {{ __('site.welcome.ram_first') }}
                                </span>
                            </div>
                        </div>

                        <!-- System topology / terminal visual -->
                        <div class="order-2 lg:order-2">
                            <div class="glass relative overflow-hidden rounded-2xl p-1.5 shadow-[0_0_70px_rgba(0,240,255,0.09)]">
                                <div class="scanline" aria-hidden="true"></div>
                                <!-- Terminal header -->
                                <div class="flex items-center justify-between border-b border-slate-800/80 px-4 py-3" dir="ltr">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2.5 w-2.5 rounded-full bg-red-400/70"></div>
                                        <div class="h-2.5 w-2.5 rounded-full bg-yellow-400/70"></div>
                                        <div class="h-2.5 w-2.5 rounded-full bg-[#39FF14]/70"></div>
                                    </div>
                                    <div class="mono text-[10px] tracking-[0.15em] text-slate-500">{{ __('site.welcome.topology_live') }}</div>
                                    <div class="mono flex items-center gap-1.5 text-[10px] text-[#39FF14]">
                                        <span class="pulse-dot h-1.5 w-1.5 rounded-full bg-[#39FF14]"></span>
                                        {{ __('site.welcome.topology_streaming') }}
                                    </div>
                                </div>

                                <!-- Topology SVG -->
                                <div class="grid-bg p-4 sm:p-6">
                                    <svg viewBox="0 0 520 340" class="h-auto w-full" fill="none" role="img" aria-label="{{ __('site.welcome.topology_aria') }}">
                                        <!-- Animated connection paths -->
                                        <defs>
                                            <linearGradient id="flow-cyan" x1="0" y1="0" x2="1" y2="0">
                                                <stop offset="0%" stop-color="#00F0FF" stop-opacity="0"/>
                                                <stop offset="50%" stop-color="#00F0FF" stop-opacity="1"/>
                                                <stop offset="100%" stop-color="#00F0FF" stop-opacity="0"/>
                                            </linearGradient>
                                            <filter id="glow-cyan"><feGaussianBlur stdDeviation="3" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
                                            <filter id="glow-green"><feGaussianBlur stdDeviation="4" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
                                        </defs>

                                        <!-- Static path lines (LTR flow) -->
                                        <g stroke-width="1.5" stroke-dasharray="5 6" opacity=".6">
                                            <path d="M112 170H196" stroke="#1e293b"/>
                                            <path d="M324 170H408" stroke="#1e293b"/>
                                            <path d="M260 208V266" stroke="#1e293b"/>
                                            <path d="M260 92V34" stroke="#1e293b"/>
                                        </g>

                                        <!-- Animated flow particles -->
                                        <g filter="url(#glow-cyan)">
                                            <path class="data-stream" d="M112 170H196" stroke="url(#flow-cyan)" stroke-width="2"/>
                                            <path class="data-stream data-stream-delayed" d="M324 170H408" stroke="url(#flow-cyan)" stroke-width="2"/>
                                        </g>

                                        <!-- Nodes: Cameras -->
                                        <g>
                                            <rect x="16" y="130" width="96" height="80" rx="10" fill="#0f172a" stroke="#00F0FF" stroke-opacity=".55" stroke-width="1.2"/>
                                            <text x="64" y="156" text-anchor="middle" fill="#E2E8F0" font-size="12" font-weight="600" font-family="Cairo, sans-serif">{{ __('site.welcome.topology_cameras') }}</text>
                                            <text x="64" y="176" text-anchor="middle" fill="#00F0FF" font-size="10" font-family="JetBrains Mono, monospace">× 8 · RTSP</text>
                                            <circle cx="30" cy="146" r="2" fill="#39FF14" class="pulse-dot"/>
                                        </g>

                                        <!-- Nodes: RK3588 Core -->
                                        <g filter="url(#glow-green)">
                                            <rect x="196" y="110" width="128" height="120" rx="12" fill="#0d2410" stroke="#39FF14" stroke-opacity=".7" stroke-width="1.5"/>
                                        </g>
                                        <text x="260" y="145" text-anchor="middle" fill="#E2E8F0" font-size="14" font-weight="700" font-family="Cairo, sans-serif">RK3588</text>
                                        <text x="260" y="166" text-anchor="middle" fill="#39FF14" font-size="10" font-family="JetBrains Mono, monospace">{{ __('site.welcome.topology_core') }}</text>
                                        <text x="260" y="188" text-anchor="middle" fill="#00F0FF" font-size="9" font-family="JetBrains Mono, monospace">YOLOv8 ONNX</text>
                                        <text x="260" y="207" text-anchor="middle" fill="#64748b" font-size="8" font-family="JetBrains Mono, monospace">OpenCV DNN</text>

                                        <!-- Nodes: Ring Buffer -->
                                        <g>
                                            <rect x="196" y="266" width="128" height="58" rx="10" fill="#0f172a" stroke="#00F0FF" stroke-opacity=".55" stroke-width="1.2"/>
                                            <text x="260" y="290" text-anchor="middle" fill="#E2E8F0" font-size="11" font-weight="600" font-family="Cairo, sans-serif">{{ __('site.welcome.topology_ring') }}</text>
                                            <text x="260" y="307" text-anchor="middle" fill="#00F0FF" font-size="9" font-family="JetBrains Mono, monospace">3s → 5s RAM</text>
                                        </g>

                                        <!-- Nodes: NVR Storage -->
                                        <g>
                                            <rect x="408" y="130" width="96" height="80" rx="10" fill="#0f172a" stroke="#00F0FF" stroke-opacity=".55" stroke-width="1.2"/>
                                            <text x="456" y="156" text-anchor="middle" fill="#E2E8F0" font-size="12" font-weight="600" font-family="Cairo, sans-serif">NVR</text>
                                            <text x="456" y="176" text-anchor="middle" fill="#39FF14" font-size="10" font-family="JetBrains Mono, monospace">{{ __('site.welcome.topology_offline') }}</text>
                                            <text x="456" y="192" text-anchor="middle" fill="#64748b" font-size="8" font-family="JetBrains Mono, monospace">{{ __('site.welcome.topology_ssd') }}</text>
                                        </g>

                                        <!-- Nodes: Air-Gap status -->
                                        <g>
                                            <rect x="196" y="14" width="128" height="48" rx="10" fill="#0f172a" stroke="#39FF14" stroke-opacity=".5" stroke-width="1.2"/>
                                            <text x="260" y="34" text-anchor="middle" fill="#E2E8F0" font-size="10" font-weight="600" font-family="Cairo, sans-serif">{{ __('site.welcome.topology_airgap') }}</text>
                                            <text x="260" y="50" text-anchor="middle" fill="#39FF14" font-size="9" font-family="JetBrains Mono, monospace">{{ __('site.welcome.topology_enforced') }}</text>
                                        </g>
                                    </svg>
                                </div>

                                <!-- Live status bar -->
                                <div class="grid grid-cols-3 divide-x divide-x-reverse divide-slate-800/80 border-t border-slate-800/80" dir="ltr">
                                    <div class="px-4 py-3 text-center">
                                        <div class="mono text-lg font-bold text-[#39FF14]">8</div>
                                        <div class="mono text-[9px] tracking-wider text-slate-500">{{ __('site.welcome.topology_cameras_label') }}</div>
                                    </div>
                                    <div class="px-4 py-3 text-center">
                                        <div class="mono text-lg font-bold text-[#00F0FF]">24ms</div>
                                        <div class="mono text-[9px] tracking-wider text-slate-500">{{ __('site.welcome.topology_inference') }}</div>
                                    </div>
                                    <div class="px-4 py-3 text-center">
                                        <div class="mono text-lg font-bold text-[#39FF14]">0%</div>
                                        <div class="mono text-[9px] tracking-wider text-slate-500">{{ __('site.welcome.topology_cloud') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════ METRICS STRIP ═══════════ -->
            <section class="relative border-y border-slate-800/60" aria-label="{{ __('site.welcome.metrics_label') }}">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid divide-y divide-slate-800/60 sm:grid-cols-3 sm:divide-x sm:divide-y-0 sm:divide-x-reverse">
                        <div class="py-6 text-center sm:px-4">
                            <div class="mono technical-ltr text-3xl font-bold text-[#39FF14] text-glow-green sm:text-4xl">100%</div>
                            <div class="mt-1.5 text-sm text-slate-400">{{ __('site.welcome.metric_offline') }}</div>
                        </div>
                        <div class="py-6 text-center sm:px-4">
                            <div class="mono technical-ltr text-3xl font-bold text-[#39FF14] text-glow-green sm:text-4xl">85%</div>
                            <div class="mt-1.5 text-sm text-slate-400">{{ __('site.welcome.metric_disk') }}</div>
                        </div>
                        <div class="py-6 text-center sm:px-4">
                            <div class="mono technical-ltr text-3xl font-bold text-[#00F0FF] text-glow-cyan sm:text-4xl">0 ms</div>
                            <div class="mt-1.5 text-sm text-slate-400">{{ __('site.welcome.metric_latency') }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══════════ DEEP TECH SOLUTION / FEATURES ═══════════ -->
            <section id="solution" class="relative py-20 sm:py-28" aria-labelledby="solution-title">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <!-- Section heading -->
                    <div class="mx-auto max-w-2xl text-center">
                        <div class="mono text-xs tracking-[0.28em] text-[#00F0FF] uppercase">{{ __('site.welcome.solution_eyebrow') }}</div>
                        <h2 id="solution-title" class="mt-4 text-3xl font-black leading-tight text-slate-50 sm:text-4xl lg:text-[2.6rem]">
                            {{ __('site.welcome.solution_title_start') }} <span class="text-[#00F0FF] text-glow-cyan">{{ __('site.welcome.solution_title_accent') }}</span> {{ __('site.welcome.solution_title_end') }}
                        </h2>
                        <p class="mt-5 text-lg leading-relaxed text-slate-400">
                            {{ __('site.welcome.solution_description') }}
                        </p>
                    </div>

                    <!-- Feature grid -->
                    <div id="features" class="mt-14 grid gap-6 md:grid-cols-3">
                        <!-- Feature 1: Zero-Cloud Edge AI -->
                        <article class="feature-card glass group relative overflow-hidden rounded-2xl p-7">
                            <!-- Corner accent -->
                            <div class="absolute -top-px right-0 h-24 w-24 bg-gradient-to-bl from-[#39FF14]/15 to-transparent" aria-hidden="true"></div>

                            <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-xl border border-[#39FF14]/35 bg-[#39FF14]/8 shadow-[0_0_20px_rgba(57,255,20,0.12)]">
                                <svg viewBox="0 0 24 24" class="h-7 w-7 text-[#39FF14]" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path d="M12 2v3M12 19v3M4.93 4.93l2.12 2.12M16.95 16.95l2.12 2.12M2 12h3M19 12h3M4.93 19.07l2.12-2.12M16.95 7.05l2.12-2.12"/>
                                    <circle cx="12" cy="12" r="5" fill="none"/>
                                </svg>
                            </div>

                            <h3 class="text-xl font-bold text-slate-100">{{ __('site.welcome.feature_ai_title') }}</h3>
                            <div class="mono mt-1 text-[10px] tracking-[0.2em] text-[#39FF14]/70 uppercase">{{ __('site.welcome.feature_ai_kicker') }}</div>

                            <p class="technical mt-4 leading-relaxed text-slate-300">
                                {{ __('site.welcome.feature_ai_description') }}
                            </p>

                            <div class="mt-6 flex flex-wrap gap-2">
                                <span class="mono technical-ltr rounded-md border border-slate-700 bg-slate-800/40 px-2.5 py-1 text-[10px] text-slate-400">ONNX</span>
                                <span class="mono technical-ltr rounded-md border border-slate-700 bg-slate-800/40 px-2.5 py-1 text-[10px] text-slate-400">OpenCV DNN</span>
                                <span class="mono technical-ltr rounded-md border border-slate-700 bg-slate-800/40 px-2.5 py-1 text-[10px] text-slate-400">YOLOv8</span>
                            </div>
                        </article>

                        <!-- Feature 2: Smart RAM Ring-Buffer -->
                        <article class="feature-card glass group relative overflow-hidden rounded-2xl p-7">
                            <div class="absolute -top-px left-0 h-24 w-24 bg-gradient-to-br from-[#00F0FF]/15 to-transparent" aria-hidden="true"></div>

                            <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-xl border border-[#00F0FF]/35 bg-[#00F0FF]/8 shadow-[0_0_20px_rgba(0,240,255,0.12)]">
                                <svg viewBox="0 0 24 24" class="h-7 w-7 text-[#00F0FF]" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/>
                                </svg>
                            </div>

                            <h3 class="text-xl font-bold text-slate-100">{{ __('site.welcome.feature_buffer_title') }}</h3>
                            <div class="mono mt-1 text-[10px] tracking-[0.2em] text-[#00F0FF]/70 uppercase">{{ __('site.welcome.feature_buffer_kicker') }}</div>

                            <p class="technical mt-4 leading-relaxed text-slate-300">
                                {{ __('site.welcome.feature_buffer_description') }}
                            </p>

                            <!-- Mini buffer visualization -->
                            <div class="mt-6 flex h-9 items-center gap-1" aria-label="{{ __('site.welcome.feature_buffer_aria') }}">
                                <div class="flex-1 rounded-sm bg-[#00F0FF]/25" style="height: 40%"></div>
                                <div class="flex-1 rounded-sm bg-[#00F0FF]/40" style="height: 55%"></div>
                                <div class="flex-1 rounded-sm bg-[#00F0FF]/60" style="height: 70%"></div>
                                <div class="flex-1 rounded-sm bg-[#00F0FF]" style="height: 100%"></div>
                                <div class="flex-1 rounded-sm bg-[#39FF14]" style="height: 100%"></div>
                                <div class="flex-1 rounded-sm bg-[#39FF14]/70" style="height: 80%"></div>
                                <div class="flex-1 rounded-sm bg-[#39FF14]/40" style="height: 60%"></div>
                                <div class="flex-1 rounded-sm bg-[#39FF14]/20" style="height: 40%"></div>
                            </div>
                            <div class="mono mt-2 flex justify-between text-[9px] text-slate-500" dir="ltr">
                                <span>{{ __('site.welcome.feature_buffer_pre') }}</span>
                                <span>{{ __('site.welcome.feature_buffer_post') }}</span>
                            </div>
                        </article>

                        <!-- Feature 3: Hardware Efficiency -->
                        <article class="feature-card glass group relative overflow-hidden rounded-2xl p-7">
                            <div class="absolute -top-px right-0 h-24 w-24 bg-gradient-to-bl from-[#00F0FF]/15 to-transparent" aria-hidden="true"></div>

                            <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-xl border border-[#00F0FF]/35 bg-[#00F0FF]/8 shadow-[0_0_20px_rgba(0,240,255,0.12)]">
                                <svg viewBox="0 0 24 24" class="h-7 w-7 text-[#00F0FF]" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <rect x="6" y="6" width="12" height="12" rx="2" fill="none"/>
                                    <path d="M9 2v4M15 2v4M9 18v4M15 18v4M2 9h4M2 15h4M18 9h4M18 15h4"/>
                                </svg>
                            </div>

                            <h3 class="text-xl font-bold text-slate-100">{{ __('site.welcome.feature_hardware_title') }}</h3>
                            <div class="mono mt-1 text-[10px] tracking-[0.2em] text-[#00F0FF]/70 uppercase">{{ __('site.welcome.feature_hardware_kicker') }}</div>

                            <p class="technical mt-4 leading-relaxed text-slate-300">
                                {{ __('site.welcome.feature_hardware_description') }}
                            </p>

                            <div class="mt-6 flex items-center gap-3">
                                <div class="mono flex-1 text-[10px] text-slate-500">{{ __('site.welcome.feature_hardware_utilization') }}</div>
                                <div class="h-1.5 flex-1.5 overflow-hidden rounded-full bg-slate-800">
                                    <div class="h-full w-[68%] rounded-full bg-gradient-to-r from-[#00F0FF] to-[#39FF14]" style="box-shadow: 0 0 10px rgba(57,255,20,.45)"></div>
                                </div>
                                <div class="mono technical-ltr text-xs font-bold text-[#00F0FF]">68%</div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <!-- ═══════════ COMPLIANCE / CTA ═══════════ -->
            <section id="compliance" class="relative py-20 sm:py-24" aria-labelledby="compliance-title">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="glass relative overflow-hidden rounded-3xl p-8 sm:p-12">
                        <!-- Ambient glow -->
                        <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-[#00F0FF]/8 blur-[90px]" aria-hidden="true"></div>
                        <div class="pointer-events-none absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-[#39FF14]/6 blur-[90px]" aria-hidden="true"></div>

                        <div class="grid items-center gap-10 lg:grid-cols-[1.3fr_1fr]">
                            <div>
                                <div class="mono text-xs tracking-[0.28em] text-[#00F0FF] uppercase">{{ __('site.welcome.compliance_eyebrow') }}</div>
                                <h2 id="compliance-title" class="mt-4 text-2xl font-black leading-tight text-slate-50 sm:text-3xl">
                                    {{ __('site.welcome.compliance_title') }} <span class="text-[#00F0FF] text-glow-cyan">{{ __('site.welcome.compliance_title_accent') }}</span>
                                </h2>
                                <p class="mt-5 max-w-2xl text-base leading-relaxed text-slate-300">
                                    {{ __('site.common.footer_legal') }}
                                </p>
                                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                                    @guest
                                        <a href="{{ route('register') }}" class="btn-primary inline-flex items-center justify-center rounded-xl border border-[#39FF14]/70 bg-[#39FF14]/14 px-6 py-3 text-sm font-bold text-[#ddffdf] shadow-[0_0_22px_rgba(57,255,20,0.2)]">
                                            <span>{{ __('site.welcome.compliance_create') }}</span>
                                        </a>
                                        <a href="{{ route('login') }}" class="btn-ghost inline-flex items-center justify-center rounded-xl border border-slate-600/80 px-6 py-3 text-sm font-semibold text-slate-300">
                                            <span>{{ __('site.welcome.compliance_login') }}</span>
                                        </a>
                                    @endguest

                                    @auth
                                        <a href="{{ $dashboardUrl }}" class="btn-primary inline-flex items-center justify-center rounded-xl border border-[#39FF14]/70 bg-[#39FF14]/14 px-6 py-3 text-sm font-bold text-[#ddffdf] shadow-[0_0_22px_rgba(57,255,20,0.2)]">
                                            <span>{{ __('site.common.dashboard') }}</span>
                                        </a>
                                    @endauth
                                </div>
                            </div>

                            <!-- Compliance badges -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="glass rounded-xl p-4 text-center">
                                    <div class="mono technical-ltr text-lg font-bold text-[#00F0FF]">18-07</div>
                                    <div class="mt-1 text-[11px] text-slate-400">{{ __('site.welcome.compliance_law') }}</div>
                                </div>
                                <div class="glass rounded-xl p-4 text-center">
                                    <div class="mono technical-ltr text-lg font-bold text-[#39FF14]">GDPR</div>
                                    <div class="mt-1 text-[11px] text-slate-400">{{ __('site.welcome.compliance_gdpr') }}</div>
                                </div>
                                <div class="glass rounded-xl p-4 text-center">
                                    <div class="mono technical-ltr text-lg font-bold text-[#00F0FF]">3s</div>
                                    <div class="mt-1 text-[11px] text-slate-400">{{ __('site.welcome.compliance_pre') }}</div>
                                </div>
                                <div class="glass rounded-xl p-4 text-center">
                                    <div class="mono technical-ltr text-lg font-bold text-[#39FF14]">5s</div>
                                    <div class="mt-1 text-[11px] text-slate-400">{{ __('site.welcome.compliance_post') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- ═══════════ FOOTER ═══════════ -->
        <footer class="relative border-t border-slate-800/70">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6 py-10 sm:flex-row sm:items-center sm:justify-between">
                    <!-- Brand -->
                    <a href="{{ route('home') }}" class="group flex items-center gap-3" aria-label="{{ __('site.common.home_aria') }}">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-[#00F0FF]/25 bg-slate-900/80">
                            <svg viewBox="0 0 64 64" class="h-5 w-5" fill="none" aria-hidden="true">
                                <circle cx="32" cy="32" r="27" stroke="#00F0FF" stroke-width="2.4" opacity=".75"/>
                                <path d="M20 39L28 19L36 27L44 15L47 39H20Z" stroke="#39FF14" stroke-width="2.4" stroke-linejoin="round"/>
                                <path d="M24 44H40" stroke="#00F0FF" stroke-width="2.4" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div>
                            <div class="mono text-[10px] tracking-[0.2em] text-[#00F0FF]/80">{{ __('site.common.brand_name') }}</div>
                            <div class="text-sm font-bold text-slate-300">{{ __('site.common.brand_local_name') }}</div>
                        </div>
                    </a>

                    <!-- Footer links -->
                    <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-slate-400">
                        <a href="#solution" class="transition hover:text-[#00F0FF]">{{ __('site.common.solution') }}</a>
                        <a href="#features" class="transition hover:text-[#00F0FF]">{{ __('site.common.features') }}</a>
                        <a href="#compliance" class="transition hover:text-[#00F0FF]">{{ __('site.common.compliance') }}</a>
                        @guest
                            <a href="{{ route('login') }}" class="transition hover:text-[#00F0FF]">{{ __('site.common.login') }}</a>
                            <a href="{{ route('register') }}" class="transition hover:text-[#00F0FF]">{{ __('site.common.signup') }}</a>
                        @endguest
                        @auth
                            <a href="{{ $dashboardUrl }}" class="transition hover:text-[#00F0FF]">{{ __('site.common.dashboard') }}</a>
                        @endauth
                    </div>
                </div>

                <div class="border-t border-slate-800/50 py-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs leading-relaxed text-slate-500 sm:max-w-2xl sm:leading-normal">
                            {{ __('site.common.footer_legal') }}
                        </p>
                        <div class="mono text-[10px] tracking-wider text-slate-600" dir="ltr">
                            {{ __('site.welcome.copyright', ['year' => date('Y')]) }}
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
