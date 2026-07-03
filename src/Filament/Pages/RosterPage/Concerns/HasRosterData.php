<?php

namespace OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Concerns;

use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use OccTherapist\AdvancedRosterForFilament\Models\RosterEntry;
use OccTherapist\AdvancedRosterForFilament\Models\RosterNote;
use OccTherapist\AdvancedRosterForFilament\Support\RosterAssigneeResolver;
use OccTherapist\AdvancedRosterForFilament\Support\RosterFilterRegistry;
use OccTherapist\AdvancedRosterForFilament\Support\RosterScopeManager;

trait HasRosterData
{
    protected function sectionKey(): string
    {
        return config('advanced-roster-for-filament.assignee_section_key', 'assignees');
    }

    #[Computed]
    public function assignees(): Collection
    {
        $scope = app(RosterScopeManager::class)->resolve();

        $assignees = app(RosterAssigneeResolver::class)
            ->getAssignees($this->sectionKey(), $scope);

        $assignees = $this->applySortOrder($assignees, 'assignee_order');

        return app(RosterFilterRegistry::class)->apply($assignees, $scope);
    }

    #[Computed]
    public function rosterEntries(): Collection
    {
        $scope = app(RosterScopeManager::class)->resolve();

        if (! $scope) {
            return collect();
        }

        $weekDays = (int) config('advanced-roster-for-filament.week_days', 5) - 1;

        return RosterEntry::query()
            ->forScope($scope)
            ->where('section_key', $this->sectionKey())
            ->where(function ($query) use ($weekDays) {
                $query->whereBetween('start_at', [
                    $this->currentDate->startOfDay(),
                    $this->currentDate->addDays($weekDays)->endOfDay(),
                ])
                    ->orWhereDate('start_at', $this->selectedDate->toDateString());
            })
            ->orderBy('start_at')
            ->get();
    }

    #[Computed]
    public function rosterNotes(): Collection
    {
        if (! config('advanced-roster-for-filament.features.notes', true)) {
            return collect();
        }

        $scope = app(RosterScopeManager::class)->resolve();

        if (! $scope) {
            return collect();
        }

        return RosterNote::query()
            ->forScope($scope)
            ->where(function ($query) {
                $query->whereBetween('date', [
                    $this->currentDate->copy()->startOfWeek(),
                    $this->currentDate->copy()->endOfWeek()->addWeek(),
                ])
                    ->orWhereDate('date', $this->selectedDate->toDateString());
            })
            ->orderBy('date')
            ->get();
    }

    private function resetComputedProperties(): void
    {
        unset($this->days);
        unset($this->rosterEntries);
        unset($this->assignees);
        unset($this->rosterNotes);
    }
}
