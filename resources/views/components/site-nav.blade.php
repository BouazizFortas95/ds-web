@props(['active' => 'home'])

@php
@endphp

<header class="relative z-10">
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-6 sm:px-6 lg:px-8" aria-label="{{ __('site.common.nav_aria') }}">
        <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="{{ __('site.common.home_aria') }}">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-cyan-400/30 bg-slate-900/90 shadow-[0_0_18px_rgba(0,240,255,0.2)]">
                <svg viewBox="0 0 64 64" class="h-7 w-7" aria-hidden="true">
                    <circle cx="32" cy="32" r="28" fill="none" stroke="#00F0FF" stroke-width="2.4" opacity="0.8"/>
                    <path d="M20 39L28 19L36 27L44 15L47 39H20Z" fill="none" stroke="#39FF14" stroke-width="2.2" stroke-linejoin="round"/>
                    <path d="M24 43H40" stroke="#00F0FF" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
            </div>
            <div>
                <div class="text-lg font-extrabold tracking-[0.06em] text-slate-100">{{ __('site.common.brand_name') }}</div>
                <div class="text-xs font-medium tracking-[0.18em] text-cyan-300">{{ __('site.common.brand_local_name') }}</div>
            </div>
        </a>

        <div class="hidden items-center gap-8 text-sm text-slate-300 md:flex">
            <a href="#solution" class="transition hover:text-cyan-300">{{ __('site.common.solution') }}</a>
            <a href="#features" class="transition hover:text-cyan-300">{{ __('site.common.features') }}</a>
            <a href="#compliance" class="transition hover:text-cyan-300">{{ __('site.common.compliance') }}</a>
        </div>

        <div class="flex items-center gap-3">
            <x-language-switcher />
            <a href="{{ route('login') }}" class="btn-cyan inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold transition">{{ __('site.common.login') }}</a>
            <a href="{{ route('register') }}" class="btn-cyan inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold transition">{{ __('site.common.signup') }}</a>
            <a href="{{ route('dashboard') }}" class="btn-primary inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold transition">{{ __('site.common.dashboard') }}</a>
        </div>
    </nav>
</header>
