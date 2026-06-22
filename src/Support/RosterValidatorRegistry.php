<?php

namespace OccTherapist\AdvancedRosterForFilament\Support;

use OccTherapist\AdvancedRosterForFilament\Contracts\RosterEntryValidator;
use OccTherapist\AdvancedRosterForFilament\Data\RosterEntryData;
use OccTherapist\AdvancedRosterForFilament\Data\ValidationResult;

class RosterValidatorRegistry
{
    /** @var array<int, RosterEntryValidator> */
    protected array $validators = [];

    public function __construct()
    {
        foreach (config('advanced-roster-for-filament.validators', []) as $validator) {
            $this->register(app($validator));
        }
    }

    public function register(RosterEntryValidator $validator): void
    {
        $this->validators[] = $validator;
    }

    public function validate(RosterEntryData $entry, ?int $excludeId = null): ValidationResult
    {
        $errors = [];

        foreach ($this->validators as $validator) {
            $result = $validator->validate($entry, $excludeId);

            if (! $result->isValid()) {
                $errors = array_merge($errors, $result->getErrors());
            }
        }

        if ($errors !== []) {
            return ValidationResult::fail($errors);
        }

        return ValidationResult::pass();
    }
}
