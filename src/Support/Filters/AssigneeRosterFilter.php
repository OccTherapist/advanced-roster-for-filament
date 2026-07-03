<?php

namespace OccTherapist\AdvancedRosterForFilament\Support\Filters;

use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Collection;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterFilter;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterScope;
use OccTherapist\AdvancedRosterForFilament\Support\RosterAssigneeResolver;
use OccTherapist\AdvancedRosterForFilament\Support\RosterScopeManager;

class AssigneeRosterFilter implements RosterFilter
{
    public function getKey(): string
    {
        return 'assignees';
    }

    public function getLabel(): string
    {
        return __('advanced-roster-for-filament::fields.assignee_ids');
    }

    public function getFormComponent(): Component
    {
        return CheckboxList::make($this->getKey())
            ->label($this->getLabel())
            ->options(fn () => $this->getAssigneeOptions())
            ->columns(2)
            ->bulkToggleable();
    }

    /**
     * @param  Collection<int, mixed>  $assignees
     * @return Collection<int, mixed>
     */
    public function apply(Collection $assignees, mixed $value, ?RosterScope $scope): Collection
    {
        if (! is_array($value) || $value === []) {
            return $assignees;
        }

        $selectedIds = array_map('strval', $value);

        return $assignees->filter(
            fn ($assignee) => in_array((string) $assignee->getKey(), $selectedIds, true),
        );
    }

    /**
     * @return array<int|string, string>
     */
    protected function getAssigneeOptions(): array
    {
        $scope = app(RosterScopeManager::class)->resolve();
        $assigneeResolver = app(RosterAssigneeResolver::class);
        $sectionKey = config('advanced-roster-for-filament.assignee_section_key', 'assignees');

        return $assigneeResolver
            ->getAssignees($sectionKey, $scope)
            ->mapWithKeys(fn ($assignee) => [
                $assignee->getKey() => $assigneeResolver->getAssigneeLabel($assignee),
            ])
            ->all();
    }
}
