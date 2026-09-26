<?php

use Spatie\TranslationLoader\LanguageLine;
use Spatie\TranslationLoader\TranslationLoaders\Db;
use Spatie\TranslationLoader\TranslationLoaderManager;
use TomatoPHP\FilamentTranslations\Models\Translation;

/*
|--------------------------------------------------------------------------
| Translation database
|--------------------------------------------------------------------------
|
| Translations live in the `language_lines` table and are served through
| `TranslationLoaderManager`, which loads the `lang/` files and then replaces
| them with the database rows:
|
|     array_replace_recursive($fileTranslations, $loaderTranslations)
|
| That is the precedence this project wants. A row edited in the panel wins, and
| a key with no row still resolves from the file, so the files remain a working
| fallback and a bad edit is never able to blank out a page.
|
| A caveat to be aware of: the manager caches every group per locale forever
| (`Cache::rememberForever`). AppServiceProvider clears those keys whenever a
| Translation row is written or deleted, without which edits in the panel would
| appear to have no effect at all.
|
*/

return [

    /*
     |--------------------------------------------------------------------------
     | Translation loaders
     |--------------------------------------------------------------------------
     |
     | Loaders consulted for each group, in order. `Db` reads the table.
     |
     */
    'translation_loaders' => [
        Db::class,
    ],

    /*
     |--------------------------------------------------------------------------
     | Model
     |--------------------------------------------------------------------------
     |
     | Points at the package's own `Translation` model rather than spatie's
     | bare `LanguageLine`, for two reasons:
     |
     | 1. It carries a SoftDeletes scope, so a row removed in the panel stops
     |    being served. The bare model has no such scope, which would mean a
     |    "deleted" translation kept coming back through `__()`.
     | 2. It is the exact model the editor writes through, so what you see in
     |    the table is what the loader reads -- same casts, same table, same
     |    translatable `text` column.
     |
     | It still extends spatie's LanguageLine, which the loader requires.
     |
     */
    'model' => Translation::class,

    /*
     |--------------------------------------------------------------------------
     | Translation manager
     |--------------------------------------------------------------------------
     |
     | Replaces Laravel's `translation.loader` so the merge described above
     | happens on every group lookup.
     |
     */
    'translation_manager' => TranslationLoaderManager::class,

];
