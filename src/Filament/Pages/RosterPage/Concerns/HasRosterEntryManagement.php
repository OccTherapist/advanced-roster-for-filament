<?php

namespace OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Concerns;

use Carbon\Carbon;
use DateTimeInterface;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterEntryTypeContract;
use OccTherapist\AdvancedRosterForFilament\Data\RosterEntryData;
use OccTherapist\AdvancedRosterForFilament\Enums\RepetitionPattern;
use OccTherapist\AdvancedRosterForFilament\Enums\Weekday;
use OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Schemas\RosterEntryForm;
use OccTherapist\AdvancedRosterForFilament\Models\RosterEntry;
use OccTherapist\AdvancedRosterForFilament\Support\RosterEntryTypeResolver;
use OccTherapist\AdvancedRosterForFilament\Support\RosterScopeManager;
use OccTherapist\AdvancedRosterForFilament\Support\RosterValidatorRegistry;

trait HasRosterEntryManagement
{
    public function addRosterEntryAction(): Action
    {
        return Action::make('addRosterEntry')
            ->modalHeading(function (array $arguments) {
                $date = Carbon::parse($arguments['date']);

                return __('advanced-roster-for-filament::messages.create_entry_for', [
                    'date' => $date->translatedFormat('l, d.m.Y'),
                ]);
            })
            ->modalWidth(Width::TwoExtraLarge)
            ->schema(fn (array $arguments) => RosterEntryForm::getSchema($arguments))
            ->action(function (array $data, array $arguments, Action $action) {
                $baseDate = Carbon::parse($arguments['date']);
                $assigneeType = $arguments['assignee_type'];
                $assigneeId = $arguments['assignee_id'];
                $sectionKey = $arguments['section_key'] ?? $this->sectionKey();

                $entryType = app(RosterEntryTypeResolver::class)->resolve($data['entry_type'] ?? null);

                if (! $entryType) {
                    $action->halt();

                    return;
                }

                $dates = $this->generateRepetitionDates($baseDate, $data);

                $createdCount = 0;
                $skippedCount = 0;
                $errors = [];
                $parentId = null;
                $scope = app(RosterScopeManager::class)->require();

                if (! count($dates)) {
                    $action->halt();
                }

                foreach ($dates as $index => $date) {
                    [$startAt, $endAt] = $this->getStartAndEndTimes($date, $entryType, $data);

                    $entryData = new RosterEntryData(
                        scope: $scope,
                        sectionKey: $sectionKey,
                        assigneeType: $assigneeType,
                        assigneeId: $assigneeId,
                        entryType: $entryType,
                        startAt: $startAt,
                        endAt: $endAt,
                        color: $data['color'] ?? null,
                        comment: $data['comment'] ?? null,
                    );

                    if (! $this->validateEntry($entryData, displayNotification: count($dates) === 1)) {
                        $skippedCount++;

                        if (count($dates) > 1) {
                            $errors[] = __('advanced-roster-for-filament::messages.overlap_on_date', [
                                'date' => $date->format('d.m.Y'),
                            ]);
                        }

                        continue;
                    }

                    if ($entryType->replacesConflictingEntries()) {
                        $this->deleteConflictingEntries($sectionKey, $assigneeType, $assigneeId, $date);
                    }

                    $record = RosterEntry::query()->create([
                        'scope_type' => $scope->getRosterScopeType(),
                        'scope_id' => $scope->getRosterScopeKey(),
                        'section_key' => $sectionKey,
                        'assignee_type' => $assigneeType,
                        'assignee_id' => $assigneeId,
                        'entry_type' => $entryType->getKey(),
                        'start_at' => $startAt,
                        'end_at' => $endAt,
                        'color' => $data['color'] ?? null,
                        'comment' => $data['comment'] ?? null,
                        'created_by' => Auth::id(),
                        'parent_id' => $parentId,
                        'repetition_pattern' => $data['repetition_pattern'] ?? null,
                        'repetition_value' => $data['repetition_value'] ?? null,
                        'repetition_interval' => $data['repetition_interval'] ?? null,
                        'repetition_weekdays' => $data['repetition_weekdays'] ?? null,
                    ]);

                    if ($index === 0 && count($dates) > 1) {
                        $parentId = $record->id;
                    }

                    $createdCount++;
                }

                $this->resetComputedProperties();

                if ($createdCount === 0 && $skippedCount > 0) {
                    $action->halt();
                }

                if (count($dates) > 1) {
                    $notification = Notification::make('repetitionEntryCreated')
                        ->title(__('advanced-roster-for-filament::messages.series_created'))
                        ->body(__('advanced-roster-for-filament::messages.series_created_body', [
                            'count' => $createdCount,
                        ]));

                    if ($skippedCount > 0) {
                        $notification->body(
                            $notification->getBody().' '.__('advanced-roster-for-filament::messages.series_skipped', [
                                'count' => $skippedCount,
                            ])
                        );
                    }

                    ($skippedCount > 0)
                        ? $notification->warning()
                        : $notification->success();

                    if ($skippedCount > 0 && count($errors) > 0) {
                        $notification->body(
                            $notification->getBody()
                            .' '
                            .implode(', ', array_slice($errors, 0, 3))
                            .(count($errors) > 3 ? '...' : '')
                        );
                    }

                    $notification->send();

                    return;
                }

                Notification::make('entryCreated')
                    ->title(__('advanced-roster-for-filament::messages.entry_created'))
                    ->success()
                    ->send();
            });
    }

    public function editRosterEntryAction(): Action
    {
        return Action::make('editRosterEntry')
            ->modalHeading(function (array $arguments) {
                $record = RosterEntry::query()->find($arguments['id'] ?? null);

                return __('advanced-roster-for-filament::messages.edit_entry_for', [
                    'date' => $record?->start_at?->translatedFormat('l, d.m.Y') ?? '',
                ]);
            })
            ->modalWidth(Width::TwoExtraLarge)
            ->fillForm(function (array $arguments): array {
                if (! $arguments['id']) {
                    return [];
                }

                $record = RosterEntry::find($arguments['id']);

                if (! $record) {
                    return [];
                }

                $data = $record->toArray();
                $data['entry_type'] = $record->entry_type;
                $data['repetition_pattern'] = $record->repetition_pattern ?? RepetitionPattern::NONE;

                $parentId = $record->parent_id ?? $record->id;
                $lastRepetition = RosterEntry::query()
                    ->where(fn ($q) => $q->where('parent_id', $parentId)->orWhere('id', $parentId))
                    ->latest('start_at')
                    ->first();

                if (! $lastRepetition) {
                    return $data;
                }

                if ($record->parent_id === null && ! RosterEntry::where('parent_id', $record->id)->exists()) {
                    return $data;
                }

                $data['repetition_until'] = $lastRepetition->start_at->toDateString();

                if (empty($record->repetition_weekdays)) {
                    $data['repetition_weekdays'] = [
                        Weekday::Monday->value,
                        Weekday::Tuesday->value,
                        Weekday::Wednesday->value,
                        Weekday::Thursday->value,
                        Weekday::Friday->value,
                    ];
                }

                return $data;
            })
            ->schema(fn (array $arguments) => RosterEntryForm::getSchema($arguments))
            ->action(function (array $data, array $arguments, Action $action) {
                $record = RosterEntry::query()->find($arguments['id']);

                if (! $record) {
                    Notification::make('noEntry')
                        ->title(__('advanced-roster-for-filament::messages.entry_not_found'))
                        ->body(__('advanced-roster-for-filament::messages.entry_not_found_body'))
                        ->danger()
                        ->send();

                    $action->cancel();
                }

                $entryType = app(RosterEntryTypeResolver::class)->resolve($data['entry_type'] ?? $record->entry_type);

                if (! $entryType) {
                    $action->cancel();

                    return;
                }

                if ($entryType->replacesConflictingEntries()) {
                    $this->deleteConflictingEntries(
                        $record->section_key,
                        $record->assignee_type,
                        $record->assignee_id,
                        $record->start_at,
                        $record->id,
                    );
                }

                [$startAt, $endAt] = $this->getStartAndEndTimes($record->start_at, $entryType, $data);
                $scope = app(RosterScopeManager::class)->require();

                $entryData = new RosterEntryData(
                    scope: $scope,
                    sectionKey: $record->section_key,
                    assigneeType: $record->assignee_type,
                    assigneeId: $record->assignee_id,
                    entryType: $entryType,
                    startAt: $startAt,
                    endAt: $endAt,
                    color: $data['color'] ?? null,
                    comment: $data['comment'] ?? null,
                );

                if (! $this->validateEntry($entryData, excludeId: $record->id)) {
                    $action->cancel();
                }

                $originalStartAt = $record->start_at->copy();

                $record->update([
                    'comment' => $data['comment'] ?? null,
                    'color' => $data['color'] ?? null,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'entry_type' => $entryType->getKey(),
                    'repetition_pattern' => $data['repetition_pattern'] ?? null,
                    'repetition_value' => $data['repetition_value'] ?? null,
                    'repetition_interval' => $data['repetition_interval'] ?? null,
                    'repetition_weekdays' => $data['repetition_weekdays'] ?? null,
                ]);

                if (! empty($data['edit_future'])) {
                    $parentId = $record->parent_id ?? $record->id;

                    $pattern = $data['repetition_pattern'] ?? null;

                    if (! ($pattern instanceof RepetitionPattern)) {
                        $pattern = RepetitionPattern::tryFrom($pattern);
                    }

                    $repetitionUntil = $data['repetition_until'] ?? null;

                    if ($pattern && $pattern !== RepetitionPattern::NONE && ! empty($repetitionUntil)) {
                        $this->recreateFutureRosterEntries(
                            record: $record,
                            parentId: $parentId,
                            pattern: $pattern,
                            repetitionUntil: $repetitionUntil,
                            data: $data,
                            entryType: $entryType,
                        );
                    } else {
                        $this->updateFutureRosterEntriesInPlace(
                            record: $record,
                            parentId: $parentId,
                            originalStartAt: $originalStartAt,
                            data: $data,
                            entryType: $entryType,
                        );
                    }
                }

                $this->resetComputedProperties();

                Notification::make('entrySaved')
                    ->title(__('advanced-roster-for-filament::messages.entry_saved'))
                    ->body(__('advanced-roster-for-filament::messages.entry_saved_body'))
                    ->success()
                    ->send();
            })
            ->extraModalFooterActions([
                Action::make('delete')
                    ->label(__('advanced-roster-for-filament::actions.delete'))
                    ->color('danger')
                    ->slideOver(false)
                    ->requiresConfirmation()
                    ->action(function (array $mountedActions, Action $action) {
                        $parentAction = $mountedActions[0] ?? null;
                        $data = $parentAction ? $parentAction->getRawData() : [];
                        $arguments = $parentAction ? $parentAction->getArguments() : [];
                        $record = RosterEntry::find($arguments['id'] ?? null);

                        if ($record) {
                            if (! empty($data['edit_future'])) {
                                $parentId = $record->parent_id ?? $record->id;

                                RosterEntry::query()
                                    ->where(fn ($q) => $q->where('parent_id', $parentId)->orWhere('id', $parentId))
                                    ->where('id', '!=', $record->id)
                                    ->where('start_at', '>', $record->start_at->toDateTimeString())
                                    ->delete();
                            }

                            $record->delete();
                        }

                        $this->resetComputedProperties();
                        $action->cancelParentActions();
                    }),
            ]);
    }

    private function getStartAndEndTimes(DateTimeInterface $date, RosterEntryTypeContract $entryType, array $data): array
    {
        if ($entryType->isAllDay()) {
            return [
                Carbon::instance($date)->startOfDay(),
                Carbon::instance($date)->endOfDay(),
            ];
        }

        return [
            Carbon::instance($date)->setTimeFrom(Carbon::parse($data['start_at'])),
            Carbon::instance($date)->setTimeFrom(Carbon::parse($data['end_at'])),
        ];
    }

    private function validateEntry(RosterEntryData $entry, ?int $excludeId = null, bool $displayNotification = true): bool
    {
        $result = app(RosterValidatorRegistry::class)->validate($entry, $excludeId);

        if ($result->isValid()) {
            return true;
        }

        if ($displayNotification) {
            Notification::make('validationFailed')
                ->warning()
                ->title(__('advanced-roster-for-filament::messages.overlap_detected'))
                ->body($result->firstError())
                ->send();
        }

        return false;
    }

    private function recreateFutureRosterEntries(
        RosterEntry $record,
        int $parentId,
        RepetitionPattern $pattern,
        string $repetitionUntil,
        array $data,
        RosterEntryTypeContract $entryType,
    ): void {
        RosterEntry::query()
            ->where(fn ($q) => $q->where('parent_id', $parentId)->orWhere('id', $parentId))
            ->where('id', '!=', $record->id)
            ->where('start_at', '>', $record->start_at->toDateTimeString())
            ->delete();

        $repetitionData = [
            'repetition_pattern' => $pattern->value,
            'repetition_value' => $data['repetition_value'] ?? 1,
            'repetition_interval' => $data['repetition_interval'] ?? null,
            'repetition_weekdays' => $data['repetition_weekdays'] ?? [],
            'repetition_until' => $repetitionUntil,
        ];

        $dates = $this->generateRepetitionDates(Carbon::instance($record->start_at), $repetitionData);
        array_shift($dates);

        $scope = app(RosterScopeManager::class)->require();

        foreach ($dates as $date) {
            [$fStartAt, $fEndAt] = $this->getStartAndEndTimes($date, $entryType, $data);

            $entryData = new RosterEntryData(
                scope: $scope,
                sectionKey: $record->section_key,
                assigneeType: $record->assignee_type,
                assigneeId: $record->assignee_id,
                entryType: $entryType,
                startAt: $fStartAt,
                endAt: $fEndAt,
                color: $data['color'] ?? null,
                comment: $data['comment'] ?? null,
            );

            if (! $this->validateEntry($entryData, displayNotification: false)) {
                continue;
            }

            RosterEntry::create([
                'scope_type' => $scope->getRosterScopeType(),
                'scope_id' => $scope->getRosterScopeKey(),
                'section_key' => $record->section_key,
                'assignee_type' => $record->assignee_type,
                'assignee_id' => $record->assignee_id,
                'entry_type' => $entryType->getKey(),
                'color' => $data['color'] ?? null,
                'start_at' => $fStartAt,
                'end_at' => $fEndAt,
                'comment' => $data['comment'] ?? null,
                'created_by' => Auth::id(),
                'parent_id' => $parentId,
                'repetition_pattern' => $pattern,
                'repetition_value' => $data['repetition_value'] ?? null,
                'repetition_interval' => $data['repetition_interval'] ?? null,
                'repetition_weekdays' => $data['repetition_weekdays'] ?? null,
            ]);
        }
    }

    private function updateFutureRosterEntriesInPlace(
        RosterEntry $record,
        int $parentId,
        DateTimeInterface $originalStartAt,
        array $data,
        RosterEntryTypeContract $entryType,
    ): void {
        $scope = app(RosterScopeManager::class)->require();

        $futureEntries = RosterEntry::query()
            ->where(fn ($q) => $q->where('parent_id', $parentId)->orWhere('id', $parentId))
            ->where('id', '!=', $record->id)
            ->where('start_at', '>', $originalStartAt->format('Y-m-d H:i:s'))
            ->orderBy('start_at')
            ->get();

        foreach ($futureEntries as $futureEntry) {
            [$futureStartAt, $futureEndAt] = $this->getStartAndEndTimes($futureEntry->start_at, $entryType, $data);

            $entryData = new RosterEntryData(
                scope: $scope,
                sectionKey: $futureEntry->section_key,
                assigneeType: $futureEntry->assignee_type,
                assigneeId: $futureEntry->assignee_id,
                entryType: $entryType,
                startAt: $futureStartAt,
                endAt: $futureEndAt,
                color: $data['color'] ?? null,
                comment: $data['comment'] ?? null,
            );

            if (! $this->validateEntry($entryData, excludeId: $futureEntry->id, displayNotification: false)) {
                continue;
            }

            $futureEntry->update([
                'comment' => $data['comment'] ?? null,
                'color' => $data['color'] ?? null,
                'start_at' => $futureStartAt,
                'end_at' => $futureEndAt,
                'entry_type' => $entryType->getKey(),
            ]);
        }
    }

    private function deleteConflictingEntries(
        string $sectionKey,
        string $assigneeType,
        int|string $assigneeId,
        DateTimeInterface $date,
        ?int $excludeId = null,
    ): void {
        $scope = app(RosterScopeManager::class)->resolve();

        if (! $scope) {
            return;
        }

        RosterEntry::query()
            ->forScope($scope)
            ->where('section_key', $sectionKey)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->where('assignee_type', $assigneeType)
            ->where('assignee_id', $assigneeId)
            ->whereDate('start_at', Carbon::instance($date)->toDateString())
            ->delete();
    }
}
