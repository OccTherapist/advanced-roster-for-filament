<?php

namespace OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Schemas;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use OccTherapist\AdvancedRosterForFilament\Concerns\HasColorPicker;
use OccTherapist\AdvancedRosterForFilament\Enums\RepetitionInterval;
use OccTherapist\AdvancedRosterForFilament\Enums\RepetitionPattern;
use OccTherapist\AdvancedRosterForFilament\Enums\Weekday;
use OccTherapist\AdvancedRosterForFilament\Livewire\RosterNoteRepetitionDetails;
use OccTherapist\AdvancedRosterForFilament\Models\RosterNote;

class RosterNoteForm
{
    use HasColorPicker;

    public static function getSchema(?array $arguments): array
    {
        $record = ! empty($arguments['id'])
            ? RosterNote::query()->find($arguments['id'])
            : null;

        if (! empty($arguments['id']) && is_null($record)) {
            return [];
        }

        return [
            Textarea::make('note')
                ->label(__('advanced-roster-for-filament::fields.note'))
                ->rows(3)
                ->required(),
            self::getColorPicker(),
            self::getRepetitionGroup($arguments, $record),
        ];
    }

    private static function resolvePattern(Get $get): RepetitionPattern
    {
        $value = $get('repetition_pattern');

        if ($value instanceof RepetitionPattern) {
            return $value;
        }

        return RepetitionPattern::tryFrom($value) ?? RepetitionPattern::NONE;
    }

    private static function getRepetitionGroup(array $arguments, ?RosterNote $record = null): Group
    {
        $date = $record?->date ?? Carbon::parse($arguments['date']);

        return Group::make()->schema([
            Select::make('repetition_pattern')
                ->label(__('advanced-roster-for-filament::fields.repetition_pattern'))
                ->helperText(function () use ($record): ?string {
                    if (! $record) {
                        return null;
                    }

                    $parentId = $record->parent_id ?? $record->id;

                    $hasRepetitions = RosterNote::query()
                        ->where(fn ($q) => $q->where('parent_id', $parentId)->orWhere('id', $parentId))
                        ->where('id', '!=', $record->id)
                        ->exists();

                    return $hasRepetitions
                        ? __('advanced-roster-for-filament::messages.repetition_warning')
                        : null;
                })
                ->options(fn () => collect(RepetitionPattern::cases())
                    ->mapWithKeys(fn ($pattern) => [$pattern->value => $pattern->getDescription($date)])
                    ->all()
                )
                ->default(RepetitionPattern::NONE)
                ->selectablePlaceholder(false)
                ->live()
                ->dehydrated(),

            Group::make()
                ->schema([
                    Group::make()
                        ->schema([
                            TextInput::make('repetition_value')
                                ->label(__('advanced-roster-for-filament::fields.repetition_value'))
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required()
                                ->columnSpan(1),

                            Select::make('repetition_interval')
                                ->label(__('advanced-roster-for-filament::fields.repetition_interval'))
                                ->options(RepetitionInterval::class)
                                ->default(RepetitionInterval::WEEK)
                                ->live()
                                ->required()
                                ->columnSpan(1),
                        ])
                        ->columns(2),

                    Select::make('repetition_weekdays')
                        ->label(__('advanced-roster-for-filament::fields.repetition_weekdays'))
                        ->helperText(__('advanced-roster-for-filament::messages.repetition_weekdays_helper'))
                        ->options(Weekday::class)
                        ->multiple()
                        ->default([
                            Weekday::Monday->value,
                            Weekday::Tuesday->value,
                            Weekday::Wednesday->value,
                            Weekday::Thursday->value,
                            Weekday::Friday->value,
                        ]),
                ])
                ->visible(fn (Get $get) => self::resolvePattern($get) === RepetitionPattern::CUSTOM),

            DatePicker::make('repetition_until')
                ->label(__('advanced-roster-for-filament::fields.repetition_until'))
                ->default(fn () => Carbon::now()->endOfYear())
                ->hintAction(fn () => Action::make('details')
                    ->label(__('advanced-roster-for-filament::actions.details'))
                    ->icon(Heroicon::OutlinedListBullet)
                    ->modalHeading(__('advanced-roster-for-filament::messages.manage_repetitions'))
                    ->modalWidth(Width::FourExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('advanced-roster-for-filament::actions.close'))
                    ->schema([
                        Livewire::make(RosterNoteRepetitionDetails::class, ['rosterNoteId' => $record?->id]),
                    ])
                    ->visible(fn () => $record)
                    ->overlayParentActions()
                )
                ->live()
                ->required()
                ->after(now()->endOfDay())
                ->visible(fn (Get $get) => self::resolvePattern($get) !== RepetitionPattern::NONE),

            Hidden::make('edit_future_is_toggleable')
                ->dehydrated(false)
                ->default(true),

            Toggle::make('edit_future')
                ->label(__('advanced-roster-for-filament::fields.edit_future'))
                ->disabled(fn (Get $get) => $get('edit_future_is_toggleable') === false)
                ->dehydrated(true)
                ->visible(function (string $operation, Get $get) use ($record) {
                    if ($operation !== 'manageRosterNote' || ! $record) {
                        return false;
                    }

                    $parentId = $record->parent_id ?? $record->id;
                    $hasFutureEntriesInSeries = RosterNote::query()
                        ->where(fn ($q) => $q->where('parent_id', $parentId)->orWhere('id', $parentId))
                        ->where('id', '!=', $record->id)
                        ->where('date', '>', $record->date->toDateString())
                        ->exists();

                    return self::resolvePattern($get) !== RepetitionPattern::NONE || $hasFutureEntriesInSeries;
                }),
        ])
            ->afterStateUpdated(function (Set $set) {
                $set('edit_future', true);
                $set('edit_future_is_toggleable', false);
            });
    }
}
