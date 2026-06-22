<?php

namespace OccTherapist\AdvancedRosterForFilament\Contracts;

use OccTherapist\AdvancedRosterForFilament\Data\RosterEntryData;
use OccTherapist\AdvancedRosterForFilament\Data\ValidationResult;

interface RosterEntryValidator
{
    public function validate(RosterEntryData $entry, ?int $excludeId = null): ValidationResult;
}
