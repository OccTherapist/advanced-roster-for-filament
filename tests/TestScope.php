<?php

namespace OccTherapist\AdvancedRosterForFilament\Tests;

use OccTherapist\AdvancedRosterForFilament\Contracts\RosterScope;

class TestScope implements RosterScope
{
    public function __construct(
        protected int|string $key = 1,
        protected string $type = 'test',
    ) {}

    public function getRosterScopeKey(): int|string
    {
        return $this->key;
    }

    public function getRosterScopeType(): string
    {
        return $this->type;
    }
}
