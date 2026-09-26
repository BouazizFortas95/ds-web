<?php

use TomatoPHP\FilamentTranslations\Filament\Resources\Translations\TranslationResource;

/*
|--------------------------------------------------------------------------
| Translation database
|--------------------------------------------------------------------------
|
| Translations are served from the `language_lines` table first and fall back to
| the `lang/` files, so a row edited here takes effect immediately while a
| missing row still resolves from git. That ordering is spatie's
| `TranslationLoaderManager`, which merges the file array and then replaces it
| with the database array -- see `config/translation-loader.php`.
|
*/

return [
    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    | The folders the scanner reads to discover translation keys.
    |
    | Deliberately excludes `base_path('vendor')`, which the package ships as a
    | default. Scanning the whole vendor tree would pull in tens of thousands of
    | unrelated keys from Filament, Livewire and their dependencies, burying the
    | project's own 128 strings in noise and making the editor unusable.
    |
    | The app's own keys come from `lang/{en,ar,fr}/site.php` and
    | `lang/{en,ar,fr}/validation.php`; scanning `resources/views` and `app`
    | surfaces every `__()` call site so a missing key is visible as a blank.
    |
    */
    'paths' => [
        app_path(),
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded paths
    |--------------------------------------------------------------------------
    |
    | Folders inside the paths above to leave alone. `lang/` is not scanned at
    | all (it holds values, not calls), but build output can appear under the
    | app path in some setups, so it is excluded defensively.
    |
    */
    'excludedPaths' => [
        base_path('storage'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | The languages offered in the editor, mirrored from
    | `config/localization.php` so there is a single source of truth for what
    | this app supports. The package's default list also ships Portuguese,
    | Burmese and Indonesian, none of which are translated here.
    |
    */
    'locals' => [
        'en' => [
            'label' => 'English',
            'flag' => 'us',
        ],
        'ar' => [
            'label' => 'العربية',
            'flag' => 'sa',
        ],
        'fr' => [
            'label' => 'Français',
            'flag' => 'fr',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Modal
    |--------------------------------------------------------------------------
    |
    | Edit translations in a modal over the table rather than on a separate
    | page. Keeps the whole catalogue browsable in one place.
    |
    */
    'modal' => true,

    /*
    |--------------------------------------------------------------------------
    |
    | Add groups that should be excluded in translation import from files to database
    |
    */
    'exclude_groups' => [],

    /*
     |--------------------------------------------------------------------------
     |
     | Register the navigation for the translations.
     |
     */
    'register_navigation' => true,

    /*
     |--------------------------------------------------------------------------
     |
     | Use Queue to scan the translations.
     |
     */
    'use_queue_on_scan' => true,

    /*
     |--------------------------------------------------------------------------
     |
     | Custom import command.
     |
     */
    'path_to_custom_import_command' => null,

    /*
     |--------------------------------------------------------------------------
     |
     | Show buttons in Translation resource.
     |
     */
    'scan_enabled' => true,
    'export_enabled' => true,
    'import_enabled' => true,

    /*
     |--------------------------------------------------------------------------
     |
     | Translation resource.
     |
     */
    'translation_resource' => TranslationResource::class,

    /*
     |--------------------------------------------------------------------------
     |
     | Custom Excel export.
     |
     */
    'path_to_custom_excel_export' => null,

    /*
     |--------------------------------------------------------------------------
     |
     | Custom Excel import.
     |
     */
    'path_to_custom_excel_import' => null,

    /*
     |--------------------------------------------------------------------------
     |
     | Use Developer Gate.
     |
     */
    'use_developer_gate' => false,

    /*
     |--------------------------------------------------------------------------
     |
     | When use developer gate, hide the navigation.
     |
     */
    'hide_navigation_when_developer_gate' => false,

    /*
     |--------------------------------------------------------------------------
     |
     | Navigation group.
     | it can be a translation key if it's has just . on it.
     |
     */
    'navigation_group' => 'Settings',

    /*
     |--------------------------------------------------------------------------
     |
     | Navigation icon.
     |
     */
    'navigation_icon' => 'heroicon-m-language',

    /*
     |--------------------------------------------------------------------------
     |
     | Policy for the Translation model.
     | Laravel cannot auto-discover a policy for a model that lives in a package,
     | so the policy is registered here. When null, App\Policies\TranslationPolicy
     | is used if it exists (for example one generated by Filament Shield).
     |
     */
    'policy' => null,
];
