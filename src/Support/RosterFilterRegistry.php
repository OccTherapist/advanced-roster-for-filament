<?php

namespace OccTherapist\AdvancedRosterForFilament\Support;

use Filament\Schemas\Components\Component;
use Illuminate\Support\Collection;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterFilter;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterScope;

class RosterFilterRegistry
{
    /** @var array<string, RosterFilter> */
    protected array $filters = [];

    public function __construct()
    {
        foreach (config('advanced-roster-for-filament.filters', []) as $filter) {
            $this->register(app($filter));
        }
    }

    public function register(RosterFilter $filter): void
    {
        $this->filters[$filter->getKey()] = $filter;
    }

    public function hasFilters(): bool
    {
        return $this->filters !== [];
    }

    /**
     * @return array<int, RosterFilter>
     */
    public function all(): array
    {
        return array_values($this->filters);
    }

    /**
     * @return array<int, Component>
     */
    public function formComponents(): array
    {
        return array_map(
            fn (RosterFilter $filter) => $filter->getFormComponent(),
            $this->all(),
        );
    }

    /**
     * @param  Collection<int, mixed>  $assignees
     * @return Collection<int, mixed>
     */
    public function apply(Collection $assignees, ?RosterScope $scope = null): Collection
    {
        if (! config('advanced-roster-for-filament.features.filters', true)) {
            return $assignees;
        }

        $filterValues = app(RosterPreferencesRepository::class)->get($scope)['filters'] ?? [];

        foreach ($this->all() as $filter) {
            $value = $filterValues[$filter->getKey()] ?? null;

            if (! $this->isActive($value)) {
                continue;
            }

            $assignees = $filter->apply($assignees, $value, $scope);
        }

        return $assignees;
    }

    public function countActive(?RosterScope $scope = null): int
    {
        $filterValues = app(RosterPreferencesRepository::class)->get($scope)['filters'] ?? [];

        return collect($this->all())
            ->filter(fn (RosterFilter $filter) => $this->isActive($filterValues[$filter->getKey()] ?? null))
            ->count();
    }

    protected function isActive(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return $value !== '' && $value !== false;
    }
}
