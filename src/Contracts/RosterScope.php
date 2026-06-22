<?php

namespace OccTherapist\AdvancedRosterForFilament\Contracts;

interface RosterScope
{
    public function getRosterScopeKey(): int|string;

    public function getRosterScopeType(): string;
}
