@props(['id' => null])

@php
    $locale = app()->getLocale();
    $locales = config('localization.locales', ['en', 'ar', 'fr']);
    $names = config('localization.names', []);

    // Overridable so a page that mounts the switcher twice can keep the two
    // controls distinguishable instead of emitting a duplicate `id`.
    $selectId = $id ?? 'ds-locale-select';
@endphp

{{--
    Page URLs carry no locale, so an option cannot be a link to "this page in
    French" -- there is no such address. Every option points at the one
    `locale.set` endpoint, which writes the cookie and bounces the visitor back
    to the page they started on.

    `dir="ltr"` is deliberate: the three option labels are read as a set, and
    letting an Arabic page mirror the control would push the chevron and the
    text to opposite ends. Each option still carries its own `lang` so the
    browser applies the right font to the name.
--}}
<div class="flex items-center gap-2" dir="ltr">
    {{-- `sr-only` rather than `hidden`: below `sm` the word is invisible but
         must stay in the accessibility tree, otherwise the control is left
         with no accessible name at all. --}}
    <label
        for="{{ $selectId }}"
        class="sr-only text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500 sm:not-sr-only"
    >
        {{ __('site.language.label') }}
    </label>

    <select
        id="{{ $selectId }}"
        data-ds-locale-select
        class="ds-locale-select rounded-lg border border-slate-700/80 bg-slate-950/60 py-1.5 pl-2.5 pr-7 text-xs font-bold uppercase tracking-[0.06em] text-slate-200 outline-none transition hover:border-slate-600 hover:text-white focus-visible:ring-2 focus-visible:ring-[#00F0FF]"
    >
        @foreach ($locales as $code)
            <option
                value="{{ route('locale.set', ['locale' => $code]) }}"
                lang="{{ $code }}"
                @selected($code === $locale)
            >{{ $names[$code] ?? $code }}</option>
        @endforeach
    </select>

    {{-- A `<select>` is inert without JavaScript. This keeps the anchors
         working for anyone scripting is off or blocked. --}}
    <noscript>
        <span class="flex items-center rounded-lg border border-slate-700/80 bg-slate-950/60 p-0.5">
            @foreach ($locales as $code)
                <a
                    href="{{ route('locale.set', ['locale' => $code]) }}"
                    lang="{{ $code }}"
                    hreflang="{{ $code }}"
                    @if ($code === $locale) aria-current="page" @endif
                    class="rounded-md px-2 py-1 text-[10px] font-bold uppercase tracking-[0.12em] transition {{ $code === $locale ? 'bg-[#00F0FF]/12 text-[#00F0FF]' : 'text-slate-500 hover:bg-white/5 hover:text-slate-200' }}"
                >{{ $code }}</a>
            @endforeach
        </span>
    </noscript>
</div>

<x-locale-select-script />
