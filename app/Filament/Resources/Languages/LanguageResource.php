<?php

namespace App\Filament\Resources\Languages;

use App\Filament\Resources\Languages\Pages\CreateLanguage;
use App\Filament\Resources\Languages\Pages\EditLanguage;
use App\Filament\Resources\Languages\Pages\ListLanguages;
use App\Filament\Resources\Languages\Schemas\LanguageForm;
use App\Filament\Resources\Languages\Tables\LanguagesTable;
use App\Models\Language;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The languages the app offers, editable without a deploy.
 *
 * This is what `config/localization.php` used to be for. Adding a language there
 * meant editing a file and shipping it; the rows here are read by
 * `App\Support\Locales`, which the locale middleware, both switchers and the
 * translation seeder all go through. The config is still the seed and the
 * fallback, so an app whose table is empty behaves exactly as it did before.
 *
 * Adding a language here gives it a place in the switcher and a column in the
 * translations editor, but it does not translate anything. There are no
 * `lang/{code}/` files, so every string in that language resolves to an empty
 * value until it is typed in, which is deliberate: a language is added when it
 * is announced and filled in afterwards, and a half-translated language reading
 * as English would hide what is still missing.
 */
class LanguageResource extends Resource
{
    protected static ?string $model = Language::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'language';

    protected static ?string $pluralModelLabel = 'languages';

    public static function form(Schema $schema): Schema
    {
        return LanguageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LanguagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLanguages::route('/'),
            'create' => CreateLanguage::route('/create'),
            'edit' => EditLanguage::route('/{record}/edit'),
        ];
    }
}
