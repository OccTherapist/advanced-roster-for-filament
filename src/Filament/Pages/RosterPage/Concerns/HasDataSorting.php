<?php

namespace OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Concerns;

use Illuminate\Support\Collection;
use OccTherapist\AdvancedRosterForFilament\Support\RosterPreferencesRepository;
use OccTherapist\AdvancedRosterForFilament\Support\RosterScopeManager;

trait HasDataSorting
{
    public function updateAssigneeOrder(array $assigneeIds): void
    {
        app(RosterPreferencesRepository::class)->save(
            ['assignee_order' => $assigneeIds],
            app(RosterScopeManager::class)->resolve(),
        );

        unset($this->assignees);

        $this->dispatch('$refresh');
    }

    public function resetAssigneeOrder(): void
    {
        app(RosterPreferencesRepository::class)->resetAssigneeOrder(
            app(RosterScopeManager::class)->resolve(),
        );

        unset($this->assignees);

        $this->dispatch('$refresh');
    }

    public function resetFilters(): void
    {
        app(RosterPreferencesRepository::class)->resetFilters(
            app(RosterScopeManager::class)->resolve(),
        );

        unset($this->assignees);

        $this->dispatch('$refresh');
    }

    private function applySortOrder(Collection $items, string $orderKey): Collection
    {
        $preferences = app(RosterPreferencesRepository::class)->get(
            app(RosterScopeManager::class)->resolve(),
        );

        $customOrder = $preferences[$orderKey] ?? null;

        if (! $customOrder) {
            return $items;
        }

        $itemsById = $items->keyBy('id');
        $orderedItems = collect();

        foreach ($customOrder as $id) {
            if ($itemsById->has($id)) {
                $orderedItems->push($itemsById->get($id));
            }
        }

        $remainingItems = $items->whereNotIn('id', $customOrder);

        return $orderedItems->concat($remainingItems);
    }
}
