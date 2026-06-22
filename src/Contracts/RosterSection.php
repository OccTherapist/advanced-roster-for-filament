<?php

namespace OccTherapist\AdvancedRosterForFilament\Contracts;

use Illuminate\Support\Collection;

interface RosterSection
{
    public function getKey(): string;

    public function getLabel(): string;

    /**
     * @return Collection<int, mixed>
     */
    public function getAssignees(?RosterScope $scope = null): Collection;
}
