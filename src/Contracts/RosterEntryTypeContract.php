<?php

namespace OccTherapist\AdvancedRosterForFilament\Contracts;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

interface RosterEntryTypeContract extends HasColor, HasIcon, HasLabel
{
    public function getKey(): string;

    public function isAllDay(): bool;

    public function replacesConflictingEntries(): bool;
}
