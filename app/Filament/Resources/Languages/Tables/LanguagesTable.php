<?php

namespace App\Filament\Resources\Languages\Tables;

use App\Models\Language;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LanguagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    // The code is the primary key, so it is also the record route
                    // key. A language's title in the panel is its name, not this.
                    ->searchable()
                    ->sortable()
                    ->description(fn (Language $record): ?string => $record->is_active
                        ? null
                        : 'Hidden from visitors'),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('direction')
                    ->label('Direction')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->color(fn (string $state): string => $state === 'rtl' ? 'warning' : 'gray'),

                // Not named `progress`: the state is formatted here rather than
                // round-tripped through `formatStateUsing()`, because a column
                // carrying an array state is re-cast on the way to the badge and
                // arrives as an integer.
                TextColumn::make('translated')
                    ->label('Translated')
                    // A language added through the panel starts empty, and without
                    // this the table would list four healthy-looking languages
                    // with no way to tell which one is still untranslated.
                    ->state(function (Language $record): string {
                        [$translated, $total] = $record->progress();

                        return sprintf('%d / %d', $translated, $total);
                    })
                    ->badge()
                    ->color(function (Language $record): string {
                        [$translated, $total] = $record->progress();

                        return match (true) {
                            $total === 0 => 'gray',
                            $translated === 0 => 'danger',
                            $translated < $total => 'warning',
                            default => 'success',
                        };
                    })
                    ->description(fn (Language $record): string => $record->progress()[0] === 0
                        ? 'Nothing translated yet'
                        : 'Strings filled in'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Offered to visitors'),
            ])
            ->recordActions([
                EditAction::make(),

                // Deleting a language is not like deleting a string: the
                // translations written for it go with it, and re-typing them is
                // the only way back. So hiding is offered as the reversible option
                // and sits before the delete in the row menu.
                Action::make('deactivate')
                    ->label('Hide')
                    ->icon('heroicon-o-eye-slash')
                    ->visible(fn (Language $record): bool => $record->is_active)
                    ->requiresConfirmation()
                    ->action(function (Language $record) {
                        $record->update(['is_active' => false]);

                        Notification::make()
                            ->title("{$record->name} is hidden")
                            ->body('Its translations are kept, and it can be shown again at any time.')
                            ->success()
                            ->send();
                    }),

                Action::make('activate')
                    ->label('Show')
                    ->icon('heroicon-o-eye')
                    ->visible(fn (Language $record): bool => ! $record->is_active)
                    ->action(fn (Language $record) => $record->update(['is_active' => true])),

                DeleteAction::make()
                    // The app has to keep at least one language, or a visitor
                    // arriving with no stored preference has nothing to be served.
                    // The button is withheld rather than clicked-then-refused, so
                    // the reason is visible before the attempt instead of after.
                    ->visible(fn (Language $record): bool => ! $record->isLastActiveLanguage())
                    ->tooltip(fn (Language $record): ?string => $record->isLastActiveLanguage()
                        ? 'This is the last available language; add or show another one first'
                        : null),
            ])
            ->toolbarActions([]);
    }
}
