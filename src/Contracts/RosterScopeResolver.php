<?php

namespace OccTherapist\AdvancedRosterForFilament\Contracts;

interface RosterScopeResolver
{
    public function resolve(): ?RosterScope;
}
