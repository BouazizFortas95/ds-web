<?php

namespace App\Filament\Resources\Languages\Pages;

use App\Filament\Resources\Languages\LanguageResource;
use App\Support\Locales;
use Filament\Resources\Pages\EditRecord;

class EditLanguage extends EditRecord
{
    protected static string $resource = LanguageResource::class;

    /**
     * The cached language list is keyed by nothing but its own name, so a rename,
     * a direction change or a show/hide all have to drop it or the change would
     * only appear after a cache clear.
     */
    protected function afterSave(): void
    {
        Locales::flush();
    }
}
