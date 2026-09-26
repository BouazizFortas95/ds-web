@php
    $locale = app()->getLocale();
    $locales = config('localization.locales', ['en', 'ar', 'fr']);
    $names = config('localization.names', []);

    $selectId = 'fi-locale-switcher-select';
@endphp

{{--
    Page URLs carry no locale, so an option cannot link to "the panel in French"
    -- there is no such address. Every option points at the one `locale.set`
    endpoint, which writes the cookie and bounces the visitor back to the
    referer, so switching language from inside the panel keeps them in the panel.

    That also retires the referer-parsing and same-origin checks this view used
    to carry: they now live once, in LocaleController, instead of per call site.

    `dir="ltr"` keeps the three option labels reading as a set; the chevron sits
    on the right regardless of page direction. Each option carries its own
    `lang` so the browser applies the correct font to the name.
--}}
<div
    class="fi-locale-switcher flex items-center"
    aria-label="{{ __('site.language.switcher') }}"
    role="navigation"
    dir="ltr"
>
    <label for="{{ $selectId }}" class="fi-sr-only">
        {{ __('site.language.switcher') }}
    </label>

    {{-- The chevron comes from the shared `.ds-locale-select` rule rather than
         Filament's `suffix-icon`, because this app has no blade-icons SVG set
         registered and `suffix-icon` throws on an unresolvable alias. `pe-6`
         trims the `padding-inline-end` that `.fi-select-input` reserves for an
         icon that is not there. --}}
    <x-filament::input.wrapper class="fi-locale-switcher-control min-w-28">
        <x-filament::input.select
            id="{{ $selectId }}"
            data-ds-locale-select
            class="fi-locale-switcher-select ds-locale-select pe-6 text-xs"
        >
            @foreach ($locales as $code)
                <option
                    value="{{ route('locale.set', ['locale' => $code]) }}"
                    lang="{{ $code }}"
                    @selected($code === $locale)
                >{{ $names[$code] ?? $code }}</option>
            @endforeach
        </x-filament::input.select>
    </x-filament::input.wrapper>

    {{-- A `<select>` is inert without JavaScript. This keeps the anchors
         working for anyone scripting is off or blocked. --}}
    <noscript>
        <span class="flex items-center gap-1">
            @foreach ($locales as $code)
                <x-filament::button
                    tag="a"
                    :href="route('locale.set', ['locale' => $code])"
                    size="xs"
                    :color="$code === $locale ? 'primary' : 'gray'"
                    :outlined="$code !== $locale"
                    :aria-label="$names[$code] ?? $code"
                    :aria-current="$code === $locale ? 'page' : null"
                    lang="{{ $code }}"
                    hreflang="{{ $code }}"
                >{{ strtoupper($code) }}</x-filament::button>
            @endforeach
        </span>
    </noscript>
</div>

<x-locale-select-script />
