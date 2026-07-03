<?php

namespace OccTherapist\AdvancedRosterForFilament\Contracts;

use Filament\Schemas\Components\Component;
use Illuminate\Support\Collection;

interface RosterFilter
{
    public function getKey(): string;

    public function getLabel(): string;

    public function getFormComponent(): Component;

    /**
     * @param  Collection<int, mixed>  $assignees
     * @return Collection<int, mixed>
     */
    public function apply(Collection $assignees, mixed $value, ?RosterScope $scope): Collection;
}
