<?php

namespace OccTherapist\AdvancedRosterForFilament\Enums;

use Carbon\CarbonInterface;
use Filament\Support\Contracts\HasLabel;

enum RepetitionInterval: string implements HasLabel
{
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DAY => __('advanced-roster-for-filament::enums.repetition_interval.day'),
            self::WEEK => __('advanced-roster-for-filament::enums.repetition_interval.week'),
            self::MONTH => __('advanced-roster-for-filament::enums.repetition_interval.month'),
            self::YEAR => __('advanced-roster-for-filament::enums.repetition_interval.year'),
        };
    }

    public function applyOn(CarbonInterface $date, int $value): CarbonInterface
    {
        return match ($this) {
            self::DAY => $date->addDays($value),
            self::WEEK => $date->addWeeks($value),
            self::MONTH => $date->addMonths($value),
            self::YEAR => $date->addYears($value),
        };
    }

    public function is(self $interval): bool
    {
        return $this->value === $interval->value;
    }
}
