<?php

namespace OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Concerns;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use OccTherapist\AdvancedRosterForFilament\Concerns\HasColorPicker;
use OccTherapist\AdvancedRosterForFilament\Enums\RepetitionPattern;
use OccTherapist\AdvancedRosterForFilament\Enums\Weekday;
use OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Schemas\RosterNoteForm;
use OccTherapist\AdvancedRosterForFilament\Models\RosterNote;
use OccTherapist\AdvancedRosterForFilament\Support\RosterScopeManager;

trait HasRosterNotesManagement
{
    use HasColorPicker;

    public function manageRosterNoteAction(): Action
    {
        return Action::make('manageRosterNote')
            ->modalHeading(function (array $arguments) {
                $date = Carbon::parse($arguments['date']);
                $label = empty($arguments['id'])
                    ? __('advanced-roster-for-filament::messages.new_note')
                    : __('advanced-roster-for-filament::messages.edit_note');

                return __('advanced-roster-for-filament::messages.note_for_date', [
                    'label' => $label,
                    'date' => $date->format('d.m.Y'),
                ]);
            })
            ->modalWidth(Width::ExtraLarge)
            ->fillForm(function (array $arguments) {
                $id = $arguments['id'] ?? null;
                $defaults = [
                    'repetition_pattern' => RepetitionPattern::NONE,
                    'repetition_until' => now()->endOfYear(),
                ];

                if (! $id) {
                    return array_merge($defaults, ['note' => null, 'color' => null]);
                }

                $note = RosterNote::find($id);

                if (! $note) {
                    return $defaults;
                }

                $data = $note->toArray();
                $data['repetition_pattern'] = $note->repetition_pattern ?? RepetitionPattern::NONE;

                $parentId = $note->parent_id ?? $note->id;
                $lastRepetition = RosterNote::query()
                    ->where(fn ($query) => $query->where('parent_id', $parentId)->orWhere('id', $parentId))
                    ->latest('date')
                    ->first();

                if (! $lastRepetition) {
                    return $data;
                }

                if ($note->parent_id === null && ! RosterNote::where('parent_id', $note->id)->exists()) {
                    return $data;
                }

                $data['repetition_until'] = $lastRepetition->date->toDateString();

                if (empty($note->repetition_weekdays)) {
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
            ->schema(fn (array $arguments) => RosterNoteForm::getSchema($arguments))
            ->action(function (array $data, array $arguments) {
                $id = $arguments['id'] ?? null;
                $baseDate = Carbon::parse($arguments['date']);
                $scope = app(RosterScopeManager::class)->require();

                if ($id) {
                    $note = RosterNote::find($id);

                    if (! $note) {
                        return;
                    }

                    $note->update([
                        'note' => $data['note'],
                        'color' => $data['color'],
                        'repetition_pattern' => $data['repetition_pattern'] ?? null,
                        'repetition_value' => $data['repetition_value'] ?? null,
                        'repetition_interval' => $data['repetition_interval'] ?? null,
                        'repetition_weekdays' => $data['repetition_weekdays'] ?? null,
                    ]);

                    if (! empty($data['edit_future'])) {
                        $parentId = $note->parent_id ?? $note->id;

                        RosterNote::query()
                            ->forScope($scope)
                            ->where(fn ($query) => $query->where('parent_id', $parentId)->orWhere('id', $parentId))
                            ->where('id', '!=', $note->id)
                            ->where('date', '>', $note->date->toDateString())
                            ->delete();

                        $pattern = $data['repetition_pattern'] ?? null;

                        if (! ($pattern instanceof RepetitionPattern)) {
                            $pattern = RepetitionPattern::tryFrom($pattern);
                        }

                        $repetitionUntil = $data['repetition_until'] ?? null;

                        if ($pattern && $pattern !== RepetitionPattern::NONE && ! empty($repetitionUntil)) {
                            $repetitionData = [
                                'repetition_pattern' => $pattern->value,
                                'repetition_value' => $data['repetition_value'] ?? 1,
                                'repetition_interval' => $data['repetition_interval'] ?? null,
                                'repetition_weekdays' => $data['repetition_weekdays'] ?? [],
                                'repetition_until' => $data['repetition_until'] ?? null,
                            ];

                            $dates = $this->generateRepetitionDates(Carbon::instance($note->date), $repetitionData);
                            array_shift($dates);

                            foreach ($dates as $date) {
                                RosterNote::create([
                                    'scope_type' => $scope->getRosterScopeType(),
                                    'scope_id' => $scope->getRosterScopeKey(),
                                    'date' => $date->toDateString(),
                                    'note' => $data['note'],
                                    'color' => $data['color'],
                                    'parent_id' => $parentId,
                                    'repetition_pattern' => $pattern->value,
                                    'repetition_value' => $data['repetition_value'] ?? 1,
                                    'repetition_interval' => $data['repetition_interval'] ?? null,
                                    'repetition_weekdays' => $data['repetition_weekdays'] ?? [],
                                ]);
                            }
                        }
                    }
                } else {
                    $dates = $this->generateRepetitionDates($baseDate, $data);
                    $parentId = null;

                    foreach ($dates as $index => $date) {
                        $record = RosterNote::query()->create([
                            'scope_type' => $scope->getRosterScopeType(),
                            'scope_id' => $scope->getRosterScopeKey(),
                            'date' => $date->toDateString(),
                            'note' => $data['note'],
                            'color' => $data['color'],
                            'parent_id' => $parentId,
                            'repetition_pattern' => $data['repetition_pattern'] ?? null,
                            'repetition_value' => $data['repetition_value'] ?? null,
                            'repetition_interval' => $data['repetition_interval'] ?? null,
                            'repetition_weekdays' => $data['repetition_weekdays'] ?? null,
                        ]);

                        if ($index === 0 && count($dates) > 1) {
                            $parentId = $record->id;
                        }
                    }
                }

                $this->resetComputedProperties();

                Notification::make()
                    ->title(__('advanced-roster-for-filament::messages.note_saved'))
                    ->success()
                    ->send();
            })
            ->extraModalFooterActions(fn (array $data, array $arguments) => [
                Action::make('deleteNote')
                    ->label(__('advanced-roster-for-filament::actions.delete'))
                    ->color('danger')
                    ->requiresConfirmation()
                    ->slideOver(false)
                    ->visible(fn () => ! empty($arguments['id']))
                    ->action(function (array $mountedActions, Action $action) use ($arguments) {
                        $parentAction = $mountedActions[0] ?? null;
                        $data = $parentAction ? $parentAction->getRawData() : [];
                        $scope = app(RosterScopeManager::class)->require();
                        $note = RosterNote::find($arguments['id'] ?? null);

                        if ($note) {
                            if (! empty($data['edit_future'])) {
                                $parentId = $note->parent_id ?? $note->id;

                                RosterNote::query()
                                    ->forScope($scope)
                                    ->where(fn ($query) => $query->where('parent_id', $parentId)->orWhere('id', $parentId))
                                    ->where('id', '!=', $note->id)
                                    ->where('date', '>', $note->date->toDateString())
                                    ->delete();
                            }

                            $note->delete();
                        }

                        $action->cancelParentActions();
                        $this->resetComputedProperties();

                        Notification::make()
                            ->title(__('advanced-roster-for-filament::messages.note_deleted'))
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
