<?php

namespace App\Filament\Resources\Languages\Pages;

use App\Filament\Resources\Languages\LanguageResource;
use App\Models\Language;
use App\Support\Locales;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateLanguage extends CreateRecord
{
    protected static string $resource = LanguageResource::class;

    /**
     * Put a new language at the end of the switcher.
     *
     * Without this the column defaults to 0, which would sort the new language
     * ahead of the existing ones -- reshuffling a switcher people already have
     * muscle memory for, in order to accommodate one that was just added.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['position'] = (int) Language::query()->max('position') + 1;

        return $data;
    }

    /**
     * Filament calls this hook with no arguments, so the record has to be read
     * back off the page rather than passed in.
     */
    protected function afterCreate(): void
    {
        Locales::flush();

        $name = $this->getRecord()?->getAttribute('name') ?? 'The language';

        // Say plainly what still has to happen. Adding a language is half the
        // job, and the other half is invisible from this screen: nothing is
        // translated yet, so every string in it currently reads as empty.
        Notification::make()
            ->title("{$name} added")
            ->success()
            ->body('It is now in the language switcher and has a column in Translations. Every string still reads as empty until it is filled in there.')
            ->persistent()
            ->send();
    }
}
