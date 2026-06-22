<?php

namespace OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Schemas;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use OccTherapist\AdvancedRosterForFilament\Concerns\HasColorPicker;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterEntryTypeContract;
use OccTherapist\AdvancedRosterForFilament\Enums\RepetitionInterval;
use OccTherapist\AdvancedRosterForFilament\Enums\RepetitionPattern;
use OccTherapist\AdvancedRosterForFilament\Enums\RosterEntryType;
use OccTherapist\AdvancedRosterForFilament\Enums\Weekday;
use OccTherapist\AdvancedRosterForFilament\Livewire\RosterEntryRepetitionDetails;
use OccTherapist\AdvancedRosterForFilament\Models\RosterEntry;
use OccTherapist\AdvancedRosterForFilament\Support\RosterEntryTypeResolver;
use OccTherapist\AdvancedRosterForFilament\Support\RosterScopeManager;

class RosterEntryForm
{
    use HasColorPicker;

    public static function getSchema(?array $arguments): array
    {
        $record = ! empty($arguments['id'])
            ? RosterEntry::query()->find($arguments['id'])
            : null;

        if (! empty($arguments['id']) && is_null($record)) {
            return [];
        }

        return [
            self::getEntryTypeToggleButtons(),
            self::getTimeEntryGroup($arguments, $record),
            self::getColorPicker()->visible(fn (Get $get) => self::resolveEntryType($get)?->getKey() === RosterEntryType::UNAVAILABLE->value),
            self::getCommentInput(),
            self::getRepetitionGroup($arguments, $record),
        ];
    }

    public static function getEntryTypeToggleButtons(): ToggleButtons
    {
        $resolver = app(RosterEntryTypeResolver::class);

        return ToggleButtons::make('entry_type')
            ->label(__('advanced-roster-for-filament::fields.entry_type'))
            ->options(collect($resolver->all())->mapWithKeys(
                fn ($type) => [$type->getKey() => $type->getLabel()]
            )->all())
            ->default(RosterEntryType::WORK->value)
            ->live()
            ->dehydrated();
    }

    private static function getTimeEntryGroup(array $arguments = [], ?RosterEntry $record = null): Group
    {
        $lastEntry = ($record || empty($arguments['date']))
            ? null
            : self::getLastEntryForDate($arguments);

        return Group::make([
            TimePicker::make('start_at')
                ->label(__('advanced-roster-for-filament::fields.start_at'))
                ->seconds(false)
                ->default(function () use ($record, $lastEntry) {
                    if ($record) {
                        return null;
                    }

                    return $lastEntry
                        ? $lastEntry->end_at->addHour()->format('H:i')
                        : config('advanced-roster-for-filament.default_start_time', '08:00');
                })
                ->before('end_at')
                ->required(fn (Get $get) => self::resolveEntryType($get)?->getKey() === RosterEntryType::WORK->value)
                ->hidden(fn (Get $get) => self::resolveEntryType($get)?->isAllDay() ?? false),

            TimePicker::make('end_at')
                ->label(__('advanced-roster-for-filament::fields.end_at'))
                ->seconds(false)
                ->default(function () use ($record, $lastEntry) {
                    if ($record) {
                        return null;
                    }

                    return $lastEntry
                        ? $lastEntry->end_at->addHours(2)->format('H:i')
                        : config('advanced-roster-for-filament.default_end_time', '09:00');
                })
                ->after('start_at')
                ->required(fn (Get $get) => self::resolveEntryType($get)?->getKey() === RosterEntryType::WORK->value)
                ->hidden(fn (Get $get) => self::resolveEntryType($get)?->isAllDay() ?? false),
        ])
            ->columns(2);
    }

    private static function getLastEntryForDate(array $arguments): ?RosterEntry
    {
        $scope = app(RosterScopeManager::class)->resolve();

        if (! $scope) {
            return null;
        }

        return RosterEntry::query()
            ->forScope($scope)
            ->where('section_key', $arguments['section_key'] ?? config('advanced-roster-for-filament.assignee_section_key'))
            ->where('assignee_type', $arguments['assignee_type'])
            ->where('assignee_id', $arguments['assignee_id'])
            ->whereDate('start_at', $arguments['date'])
            ->latest('start_at')
            ->first();
    }

    private static function getCommentInput(): Textarea
    {
        return Textarea::make('comment')
            ->label(__('advanced-roster-for-filament::fields.comment'))
            ->rows(2);
    }

    private static function resolvePattern(Get $get): RepetitionPattern
    {
        $value = $get('repetition_pattern');

        if ($value instanceof RepetitionPattern) {
            return $value;
        }

        return RepetitionPattern::tryFrom($value) ?? RepetitionPattern::NONE;
    }

    private static function resolveEntryType(Get $get): ?RosterEntryTypeContract
    {
        return app(RosterEntryTypeResolver::class)->resolve($get('entry_type'));
    }

    private static function getRepetitionGroup(array $arguments, ?RosterEntry $record = null): Group
    {
        $date = $record?->start_at ?? Carbon::parse($arguments['date']);

        return Group::make()->schema([
            Select::make('repetition_pattern')
                ->label(__('advanced-roster-for-filament::fields.repetition_pattern'))
                ->helperText(function () use ($record): ?string {
                    if (! $record) {
                        return null;
                    }

                    $parentId = $record->parent_id ?? $record->id;

                    $hasRepetitions = RosterEntry::query()
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
                        Livewire::make(RosterEntryRepetitionDetails::class, ['rosterEntryId' => $record?->id]),
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
                    if ($operation !== 'editRosterEntry' || ! $record) {
                        return false;
                    }

                    $parentId = $record->parent_id ?? $record->id;
                    $hasFutureEntriesInSeries = RosterEntry::query()
                        ->where(fn ($q) => $q->where('parent_id', $parentId)->orWhere('id', $parentId))
                        ->where('id', '!=', $record->id)
                        ->where('start_at', '>', $record->start_at->toDateTimeString())
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
