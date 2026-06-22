<?php

namespace OccTherapist\AdvancedRosterForFilament\Data;

class ValidationResult
{
    /**
     * @param  array<int, string>  $errors
     */
    public function __construct(
        public readonly bool $valid,
        public readonly array $errors = [],
    ) {}

    public static function pass(): self
    {
        return new self(valid: true);
    }

    /**
     * @param  array<int, string>|string  $errors
     */
    public static function fail(array|string $errors): self
    {
        $errors = is_array($errors) ? $errors : [$errors];

        return new self(valid: false, errors: $errors);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors[0] ?? null;
    }
}
