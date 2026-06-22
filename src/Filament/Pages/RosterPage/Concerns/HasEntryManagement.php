<?php

namespace OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Concerns;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Livewire\Attributes\On;
use OccTherapist\AdvancedRosterForFilament\Data\RosterEntryData;
use OccTherapist\AdvancedRosterForFilament\Models\RosterEntry;
use OccTherapist\AdvancedRosterForFilament\Support\RosterEntryTypeResolver;
use OccTherapist\AdvancedRosterForFilament\Support\RosterScopeManager;

trait HasEntryManagement
{
    public ?array $pendingMoveData = null;

    public function chooseMoveOrCopyAction(): Action
    {
        return Action::make('chooseMoveOrCopy')
            ->modalHeading(__('advanced-roster-for-filament::messages.move_or_copy_heading'))
            ->modalDescription(__('advanced-roster-for-filament::messages.move_or_copy_description'))
            ->modalWidth(Width::Medium)
            ->slideOver(false)
            ->modalSubmitAction(false)
            ->extraModalFooterActions([
                Action::make('move')
                    ->label(__('advanced-roster-for-filament::actions.move'))
                    ->color('primary')
                    ->action(function (Action $action) {
                        if ($this->pendingMoveData) {
                            $entryId = $this->pendingMoveData['entryId'];
                            $newDate = $this->pendingMoveData['newDate'];
                            $newAssigneeType = $this->pendingMoveData['newAssigneeType'];
                            $newAssigneeId = $this->pendingMoveData['newAssigneeId'];

                            $entry = RosterEntry::findOrFail($entryId);
                            $hasChildren = $entry->children()->where('start_at', '>', $entry->start_at)->exists();
                            $isParent = RosterEntry::where('parent_id', $entry->id)->where('start_at', '>', $entry->start_at)->exists();
                            $isChild = $entry->parent_id !== null;

                            if ($hasChildren || $isParent || $isChild) {
                                $this->dispatch('open-move-modal');
                            } else {
                                $this->performMove($entryId, $newDate, $newAssigneeType, $newAssigneeId, false);
                                $this->pendingMoveData = null;
                            }

                            $action->cancelParentActions();
                        }
                    }),

                Action::make('copy')
                    ->label(__('advanced-roster-for-filament::actions.copy'))
                    ->color('gray')
                    ->action(function (Action $action) {
                        if ($this->pendingMoveData) {
                            $entryId = $this->pendingMoveData['entryId'];
                            $newDate = $this->pendingMoveData['newDate'];
                            $newAssigneeType = $this->pendingMoveData['newAssigneeType'];
                            $newAssigneeId = $this->pendingMoveData['newAssigneeId'];

                            $entry = RosterEntry::findOrFail($entryId);
                            $hasChildren = $entry->children()->where('start_at', '>', $entry->start_at)->exists();
                            $isParent = RosterEntry::where('parent_id', $entry->id)->where('start_at', '>', $entry->start_at)->exists();
                            $isChild = $entry->parent_id !== null;

                            if ($hasChildren || $isParent || $isChild) {
                                $this->dispatch('open-copy-modal');
                            } else {
                                $this->performCopy($entryId, $newDate, $newAssigneeType, $newAssigneeId, false);
                                $this->pendingMoveData = null;
                            }

                            $action->cancelParentActions();
                        }
                    }),
            ]);
    }

    public function chooseCopyOptionAction(): Action
    {
        return Action::make('chooseCopyOption')
            ->modalHeading(__('advanced-roster-for-filament::messages.copy_heading'))
            ->modalDescription(__('advanced-roster-for-filament::messages.copy_description'))
            ->modalWidth(Width::Large)
            ->slideOver(false)
            ->modalSubmitAction(false)
            ->extraModalFooterActions([
                Action::make('copyCurrentOnly')
                    ->label(__('advanced-roster-for-filament::messages.copy_current_only'))
                    ->color('primary')
                    ->action(function (Action $action) {
                        if ($this->pendingMoveData) {
                            $this->performCopy(
                                $this->pendingMoveData['entryId'],
                                $this->pendingMoveData['newDate'],
                                $this->pendingMoveData['newAssigneeType'],
                                $this->pendingMoveData['newAssigneeId'],
                                false,
                            );

                            $this->pendingMoveData = null;
                            $action->cancelParentActions();
                        }
                    }),

                Action::make('copyFuture')
                    ->label(__('advanced-roster-for-filament::messages.copy_future'))
                    ->color('gray')
                    ->action(function (Action $action) {
                        if ($this->pendingMoveData) {
                            $this->performCopy(
                                $this->pendingMoveData['entryId'],
                                $this->pendingMoveData['newDate'],
                                $this->pendingMoveData['newAssigneeType'],
                                $this->pendingMoveData['newAssigneeId'],
                                true,
                            );
                            $this->pendingMoveData = null;
                            $action->cancelParentActions();
                        }
                    }),
            ]);
    }

    public function chooseMoveOptionAction(): Action
    {
        return Action::make('chooseMoveOption')
            ->modalHeading(__('advanced-roster-for-filament::messages.move_heading'))
            ->modalDescription(__('advanced-roster-for-filament::messages.move_description'))
            ->modalWidth(Width::Large)
            ->slideOver(false)
            ->modalSubmitAction(false)
            ->extraModalFooterActions([
                Action::make('moveCurrentOnly')
                    ->label(__('advanced-roster-for-filament::messages.move_current_only'))
                    ->color('primary')
                    ->action(function (Action $action) {
                        if ($this->pendingMoveData) {
                            $this->performMove(
                                $this->pendingMoveData['entryId'],
                                $this->pendingMoveData['newDate'],
                                $this->pendingMoveData['newAssigneeType'],
                                $this->pendingMoveData['newAssigneeId'],
                                false,
                            );

                            $this->pendingMoveData = null;
                            $action->cancelParentActions();
                        }
                    }),

                Action::make('moveFuture')
                    ->label(__('advanced-roster-for-filament::messages.move_future'))
                    ->color('gray')
                    ->action(function (Action $action) {
                        if ($this->pendingMoveData) {
                            $this->performMove(
                                $this->pendingMoveData['entryId'],
                                $this->pendingMoveData['newDate'],
                                $this->pendingMoveData['newAssigneeType'],
                                $this->pendingMoveData['newAssigneeId'],
                                true,
                            );
                            $this->pendingMoveData = null;
                            $action->cancelParentActions();
                        }
                    }),
            ]);
    }

    #[On('request-move-entry')]
    public function requestMoveEntry(int $entryId, string $date, string $assigneeType, int|string $assigneeId): void
    {
        $this->pendingMoveData = [
            'entryId' => $entryId,
            'newDate' => $date,
            'newAssigneeType' => $assigneeType,
            'newAssigneeId' => $assigneeId,
        ];

        $this->mountAction('chooseMoveOrCopy');
    }

    #[On('open-move-modal')]
    public function openMoveModal(): void
    {
        $this->mountAction('chooseMoveOption');
    }

    #[On('open-choice-modal')]
    public function openChoiceModal(): void
    {
        $this->mountAction('chooseMoveOrCopy');
    }

    #[On('open-copy-modal')]
    public function openCopyModal(): void
    {
        $this->mountAction('chooseCopyOption');
    }

    protected function performCopy(
        int $entryId,
        string $newDate,
        string $newAssigneeType,
        int|string $newAssigneeId,
        bool $copyFuture = false,
    ): void {
        $entry = RosterEntry::findOrFail($entryId);
        $newDateCarbon = Carbon::parse($newDate);
        $entryType = app(RosterEntryTypeResolver::class)->resolve($entry->entry_type);

        if (! $entryType) {
            return;
        }

        $originalStart = $entry->start_at;
        $originalEnd = $entry->end_at;
        $newStartAt = $newDateCarbon->copy()->setTimeFrom($originalStart);
        $newEndAt = $newDateCarbon->copy()->setTimeFrom($originalEnd);
        $scope = app(RosterScopeManager::class)->require();

        $entryData = new RosterEntryData(
            scope: $scope,
            sectionKey: $entry->section_key,
            assigneeType: $newAssigneeType,
            assigneeId: $newAssigneeId,
            entryType: $entryType,
            startAt: $newStartAt,
            endAt: $newEndAt,
            color: $entry->color,
            comment: $entry->comment,
        );

        if (! $this->validateEntry($entryData)) {
            Notification::make('copyError')
                ->danger()
                ->title(__('advanced-roster-for-filament::messages.copy_error'))
                ->body(__('advanced-roster-for-filament::messages.copy_error_overlap'))
                ->send();

            return;
        }

        $dateDifference = $originalStart->diffInDays($newDateCarbon, false);

        $newRecord = $entry->replicate()->fill([
            'assignee_type' => $newAssigneeType,
            'assignee_id' => $newAssigneeId,
            'start_at' => $newStartAt,
            'end_at' => $newEndAt,
            'parent_id' => null,
        ]);
        $newRecord->save();

        $copiedCount = 0;

        if ($copyFuture) {
            $parentId = $entry->parent_id ?? $entry->id;
            $futureRecords = RosterEntry::query()
                ->where(fn ($query) => $query->where('parent_id', $parentId)->orWhere('id', $parentId))
                ->where('id', '!=', $entry->id)
                ->where('start_at', '>', $entry->start_at->toDateTimeString())
                ->get();

            $newParentId = $newRecord->id;

            foreach ($futureRecords as $futureRecord) {
                $futureEntryType = app(RosterEntryTypeResolver::class)->resolve($futureRecord->entry_type);

                if (! $futureEntryType) {
                    continue;
                }

                $futureNewDate = $futureRecord->start_at->copy()->addDays($dateDifference);
                $futureNewStartAt = $futureNewDate->copy()->setTimeFrom($futureRecord->start_at);
                $futureNewEndAt = $futureNewDate->copy()->setTimeFrom($futureRecord->end_at);

                $futureEntryData = new RosterEntryData(
                    scope: $scope,
                    sectionKey: $futureRecord->section_key,
                    assigneeType: $newAssigneeType,
                    assigneeId: $newAssigneeId,
                    entryType: $futureEntryType,
                    startAt: $futureNewStartAt,
                    endAt: $futureNewEndAt,
                    color: $futureRecord->color,
                    comment: $futureRecord->comment,
                );

                if (! $this->validateEntry($futureEntryData, displayNotification: false)) {
                    continue;
                }

                $futureRecord->replicate()->fill([
                    'assignee_type' => $newAssigneeType,
                    'assignee_id' => $newAssigneeId,
                    'start_at' => $futureNewStartAt,
                    'end_at' => $futureNewEndAt,
                    'parent_id' => $newParentId,
                ])->save();

                $copiedCount++;
            }
        }

        $this->resetComputedProperties();

        $message = $copyFuture && $copiedCount > 0
            ? __('advanced-roster-for-filament::messages.entry_copied_future_body', ['count' => $copiedCount])
            : __('advanced-roster-for-filament::messages.entry_copied_body');

        Notification::make('entryCopied')
            ->success()
            ->title(__('advanced-roster-for-filament::messages.entry_copied'))
            ->body($message)
            ->send();
    }

    protected function performMove(
        int $entryId,
        string $newDate,
        string $newAssigneeType,
        int|string $newAssigneeId,
        bool $moveFuture = false,
    ): void {
        $entry = RosterEntry::findOrFail($entryId);
        $newDateCarbon = Carbon::parse($newDate);
        $entryType = app(RosterEntryTypeResolver::class)->resolve($entry->entry_type);

        if (! $entryType) {
            return;
        }

        $originalStart = $entry->start_at;
        $originalEnd = $entry->end_at;
        $newStartAt = $newDateCarbon->copy()->setTimeFrom($originalStart);
        $newEndAt = $newDateCarbon->copy()->setTimeFrom($originalEnd);
        $scope = app(RosterScopeManager::class)->require();

        $entryData = new RosterEntryData(
            scope: $scope,
            sectionKey: $entry->section_key,
            assigneeType: $newAssigneeType,
            assigneeId: $newAssigneeId,
            entryType: $entryType,
            startAt: $newStartAt,
            endAt: $newEndAt,
            color: $entry->color,
            comment: $entry->comment,
        );

        if (! $this->validateEntry($entryData, excludeId: $entry->id)) {
            Notification::make('moveError')
                ->danger()
                ->title(__('advanced-roster-for-filament::messages.move_error'))
                ->body(__('advanced-roster-for-filament::messages.move_error_overlap'))
                ->send();

            return;
        }

        $dateDifference = $originalStart->diffInDays($newDateCarbon, false);

        $entry->update([
            'assignee_type' => $newAssigneeType,
            'assignee_id' => $newAssigneeId,
            'start_at' => $newStartAt,
            'end_at' => $newEndAt,
        ]);

        $movedCount = 0;

        if ($moveFuture) {
            $parentId = $entry->parent_id ?? $entry->id;
            $futureRecords = RosterEntry::query()
                ->where(fn ($query) => $query->where('parent_id', $parentId)->orWhere('id', $parentId))
                ->where('id', '!=', $entry->id)
                ->where('start_at', '>', $entry->start_at->toDateTimeString())
                ->get();

            foreach ($futureRecords as $futureRecord) {
                $futureEntryType = app(RosterEntryTypeResolver::class)->resolve($futureRecord->entry_type);

                if (! $futureEntryType) {
                    continue;
                }

                $futureNewDate = $futureRecord->start_at->copy()->addDays($dateDifference);
                $futureNewStartAt = $futureNewDate->copy()->setTimeFrom($futureRecord->start_at);
                $futureNewEndAt = $futureNewDate->copy()->setTimeFrom($futureRecord->end_at);

                $futureEntryData = new RosterEntryData(
                    scope: $scope,
                    sectionKey: $futureRecord->section_key,
                    assigneeType: $newAssigneeType,
                    assigneeId: $newAssigneeId,
                    entryType: $futureEntryType,
                    startAt: $futureNewStartAt,
                    endAt: $futureNewEndAt,
                    color: $futureRecord->color,
                    comment: $futureRecord->comment,
                );

                if (! $this->validateEntry($futureEntryData, excludeId: $futureRecord->id, displayNotification: false)) {
                    continue;
                }

                $futureRecord->update([
                    'assignee_type' => $newAssigneeType,
                    'assignee_id' => $newAssigneeId,
                    'start_at' => $futureNewStartAt,
                    'end_at' => $futureNewEndAt,
                ]);

                $movedCount++;
            }
        }

        $this->resetComputedProperties();

        $message = $moveFuture && $movedCount > 0
            ? __('advanced-roster-for-filament::messages.entry_moved_future_body', ['count' => $movedCount])
            : __('advanced-roster-for-filament::messages.entry_moved_body');

        Notification::make('entryMoved')
            ->success()
            ->title(__('advanced-roster-for-filament::messages.entry_moved'))
            ->body($message)
            ->send();
    }
}
