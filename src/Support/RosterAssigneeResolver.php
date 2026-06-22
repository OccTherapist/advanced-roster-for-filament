<?php

namespace OccTherapist\AdvancedRosterForFilament\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterScope;

class RosterAssigneeResolver
{
    public function __construct(
        protected RosterSectionRegistry $sections,
        protected RosterScopeManager $scopeManager,
    ) {}

    public function getAssignees(?string $sectionKey = null, ?RosterScope $scope = null): Collection
    {
        $sectionKey ??= config('advanced-roster-for-filament.assignee_section_key', 'assignees');
        $scope ??= $this->scopeManager->resolve();

        if ($this->sections->has($sectionKey)) {
            return $this->sections->getAssignees($sectionKey, $scope);
        }

        return $this->defaultAssignees($scope);
    }

    protected function defaultAssignees(?RosterScope $scope): Collection
    {
        $modelClass = config('advanced-roster-for-filament.assignee_model');

        if (! class_exists($modelClass)) {
            return collect();
        }

        $query = $modelClass::query();

        $model = new $modelClass;

        if (method_exists($model, 'scopeForRoster')) {
            $query = $model->scopeForRoster($query, $scope);
        }

        $nameColumn = method_exists($model, 'getRosterNameColumn')
            ? $model->getRosterNameColumn()
            : config('advanced-roster-for-filament.assignee_name_column', 'name');

        if (is_string($nameColumn) && $nameColumn !== '') {
            $query->orderBy($nameColumn);
        }

        return $query->get()->filter(function (Model $assignee) {
            if (! method_exists($assignee, 'isVisibleOnRoster')) {
                return true;
            }

            return $assignee->isVisibleOnRoster(now());
        });
    }

    public function resolveAssignee(string $type, int|string $id): ?Model
    {
        if (! class_exists($type)) {
            return null;
        }

        return $type::query()->find($id);
    }

    public function getAssigneeLabel(Model $assignee): string
    {
        if (method_exists($assignee, 'getRosterLabel')) {
            return $assignee->getRosterLabel();
        }

        if (method_exists($assignee, 'getRosterAssigneeLabel')) {
            return $assignee->getRosterAssigneeLabel();
        }

        if (isset($assignee->name)) {
            return (string) $assignee->name;
        }

        if (isset($assignee->full_name)) {
            return (string) $assignee->full_name;
        }

        if (isset($assignee->first_name, $assignee->last_name)) {
            return trim("{$assignee->first_name} {$assignee->last_name}");
        }

        return (string) $assignee->getKey();
    }
}
