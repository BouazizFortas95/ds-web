<?php

namespace App\Filament\Resources\Languages\Schemas;

use App\Models\Language;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->description('The code is a language tag such as es, ar or zh_CN. It has to match a locale Laravel knows, because it is the directory name under lang/ and the key used in the ds_locale cookie.')
                    ->schema([
                        TextInput::make('code')
                            ->label('Language code')
                            ->required()
                            ->maxLength(12)
                            // Uppercase and strip spaces as they are typed, so the
                            // stored value is a tag rather than whatever was typed.
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (TextInput $component, ?string $state) => $component->state(
                                strtolower(str_replace(' ', '_', trim((string) $state))),
                            ))
                            // Changing the key would orphan every translation cell
                            // already written for this language, so it is fixed once
                            // the language exists.
                            ->disabled(fn (string $operation) => $operation === 'edit')
                            ->unique(ignoreRecord: true)
                            ->rule([
                                // Deliberately narrow. This value reaches
                                // lang_path() in the seeder, so a tag containing a
                                // slash or a dot has to be unstorable rather than
                                // merely discouraged.
                                'regex:/^[a-z]{2,3}(_[A-Za-z]{2,4})?$/',
                            ])
                            ->helperText('Lowercase, two or three letters. An optional region suffix such as _CN is allowed.'),

                        TextInput::make('name')
                            ->label('Name in the language')
                            ->required()
                            ->maxLength(64)
                            ->helperText('Written the way a speaker of it would write it, because this is the switcher label.'),

                        Select::make('direction')
                            ->label('Text direction')
                            ->required()
                            ->options([
                                'ltr' => 'Left to right',
                                'rtl' => 'Right to left',
                            ])
                            // Pre-select the usual direction for the code so the
                            // common case needs no thought, while leaving the
                            // answer editable: script depends on the region as much
                            // as the language, so `uz` is Cyrillic in Uzbekistan and
                            // Latin in Afghanistan.
                            ->default(fn (?string $state) => Language::guessDirection((string) $state))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Select $component, ?string $state) => $component->state(
                                Language::guessDirection((string) $state),
                            )),

                        Toggle::make('is_active')
                            ->label('Offer in the language switcher')
                            ->helperText('Turn this off to keep the settings of a language but hide it from visitors, which is useful while it is still being translated.')
                            ->default(true),

                        Toggle::make('is_default')
                            ->label('Configured default')
                            ->helperText('Seeded from localization.default. It only takes effect if that setting names a language which is no longer available.')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }
}
