<?php

namespace OccTherapist\AdvancedRosterForFilament\Data;

use Carbon\CarbonInterface;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterEntryTypeContract;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterScope;

class RosterEntryData
{
    public function __construct(
        public readonly RosterScope $scope,
        public readonly string $sectionKey,
        public readonly string $assigneeType,
        public readonly int|string $assigneeId,
        public readonly RosterEntryTypeContract $entryType,
        public readonly CarbonInterface $startAt,
        public readonly CarbonInterface $endAt,
        public readonly ?array $color = null,
        public readonly ?string $comment = null,
    ) {}

    public function getEntryTypeKey(): string
    {
        return $this->entryType->getKey();
    }
}
