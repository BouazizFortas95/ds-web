{{--
    Navigating a language `<select>` requires script, so the change is delegated
    from the document rather than bound per control. Delegation also means the
    listener survives Livewire morphing the Filament topbar, which replaces the
    switcher markup without re-running any page-level script.

    This lives in a partial because the public pages and the Filament panel ship
    different asset bundles and cannot share a module. The `window` guard makes
    repeat inclusion harmless, which matters because a page can render the
    switcher in more than one place.
--}}
<script>
    (function () {
        if (window.dsLocaleSelectBound) {
            return;
        }

        window.dsLocaleSelectBound = true;

        document.addEventListener('change', function (event) {
            if (! (event.target instanceof Element)) {
                return;
            }

            var select = event.target.closest('[data-ds-locale-select]');

            if (! select || ! select.value) {
                return;
            }

            window.location.assign(select.value);
        });
    })();
</script>
