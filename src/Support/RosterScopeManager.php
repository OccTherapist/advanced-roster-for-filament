<?php

namespace OccTherapist\AdvancedRosterForFilament\Support;

use OccTherapist\AdvancedRosterForFilament\Contracts\RosterScope;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterScopeResolver;

class RosterScopeManager
{
    public function __construct(
        protected RosterScopeResolver $resolver,
    ) {}

    public function resolve(): ?RosterScope
    {
        return $this->resolver->resolve();
    }

    public function require(): RosterScope
    {
        $scope = $this->resolve();

        if (! $scope) {
            throw new \RuntimeException(__('advanced-roster-for-filament::messages.scope_not_resolved'));
        }

        return $scope;
    }
}
