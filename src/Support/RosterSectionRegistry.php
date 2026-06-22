<?php

namespace OccTherapist\AdvancedRosterForFilament\Support;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterScope;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterSection;

class RosterSectionRegistry
{
    /** @var array<string, RosterSection> */
    protected array $sections = [];

    public function register(RosterSection $section): void
    {
        $this->sections[$section->getKey()] = $section;
    }

    public function get(string $key): RosterSection
    {
        if (! isset($this->sections[$key])) {
            throw new InvalidArgumentException("Roster section [{$key}] is not registered.");
        }

        return $this->sections[$key];
    }

    public function has(string $key): bool
    {
        return isset($this->sections[$key]);
    }

    /**
     * @return Collection<string, RosterSection>
     */
    public function all(): Collection
    {
        return collect($this->sections);
    }

    public function getAssignees(string $key, ?RosterScope $scope = null): Collection
    {
        return $this->get($key)->getAssignees($scope);
    }
}
