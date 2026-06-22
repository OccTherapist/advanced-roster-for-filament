<?php

namespace OccTherapist\AdvancedRosterForFilament\Support;

use Closure;
use Illuminate\Support\Collection;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterScope;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterSection;

class ConfigRosterSection implements RosterSection
{
    public function __construct(
        protected string $key,
        protected string $label,
        protected string $assigneeModel,
        protected ?Closure $assigneeQuery = null,
        protected ?Closure $labelResolver = null,
    ) {}

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getAssignees(?RosterScope $scope = null): Collection
    {
        $query = $this->assigneeModel::query();

        if ($this->assigneeQuery) {
            ($this->assigneeQuery)($query, $scope);
        }

        return $query->get();
    }

    public static function fromConfig(string $key, array $config): self
    {
        return new self(
            key: $key,
            label: $config['label'] ?? $key,
            assigneeModel: $config['model'] ?? config('advanced-roster-for-filament.assignee_model'),
            assigneeQuery: $config['query'] ?? null,
            labelResolver: $config['label_resolver'] ?? null,
        );
    }
}
