<?php

namespace OccTherapist\AdvancedRosterForFilament\Support\Validators;

use OccTherapist\AdvancedRosterForFilament\Contracts\RosterEntryValidator;
use OccTherapist\AdvancedRosterForFilament\Data\RosterEntryData;
use OccTherapist\AdvancedRosterForFilament\Data\ValidationResult;
use OccTherapist\AdvancedRosterForFilament\Models\RosterEntry;

class OverlapRosterEntryValidator implements RosterEntryValidator
{
    public function validate(RosterEntryData $entry, ?int $excludeId = null): ValidationResult
    {
        if (! config('advanced-roster-for-filament.validate_overlap', true)) {
            return ValidationResult::pass();
        }

        $exists = RosterEntry::query()
            ->forScope($entry->scope)
            ->where('section_key', $entry->sectionKey)
            ->where('assignee_type', $entry->assigneeType)
            ->where('assignee_id', $entry->assigneeId)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->where(function ($query) use ($entry) {
                $query->where('start_at', '<', $entry->endAt)
                    ->where('end_at', '>', $entry->startAt);
            })
            ->exists();

        if ($exists) {
            return ValidationResult::fail(__('advanced-roster-for-filament::messages.overlap_detected'));
        }

        return ValidationResult::pass();
    }
}
