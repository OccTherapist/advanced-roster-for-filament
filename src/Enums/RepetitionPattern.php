<?php

namespace OccTherapist\AdvancedRosterForFilament\Enums;

use Carbon\CarbonInterface;
use Filament\Support\Contracts\HasLabel;

enum RepetitionPattern: string implements HasLabel
{
    case NONE = 'none';
    case WEEKDAYS = 'weekdays';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
    case CUSTOM = 'custom';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NONE => __('advanced-roster-for-filament::enums.repetition_pattern.none'),
            self::WEEKDAYS => __('advanced-roster-for-filament::enums.repetition_pattern.weekdays'),
            self::WEEKLY => __('advanced-roster-for-filament::enums.repetition_pattern.weekly'),
            self::MONTHLY => __('advanced-roster-for-filament::enums.repetition_pattern.monthly'),
            self::YEARLY => __('advanced-roster-for-filament::enums.repetition_pattern.yearly'),
            self::CUSTOM => __('advanced-roster-for-filament::enums.repetition_pattern.custom'),
        };
    }

    public function getDescription(CarbonInterface $baseDate): string
    {
        return match ($this) {
            self::NONE => __('advanced-roster-for-filament::enums.repetition_pattern.description.none', [
                'date' => $baseDate->format('d.m.Y'),
            ]),
            self::WEEKDAYS => __('advanced-roster-for-filament::enums.repetition_pattern.description.weekdays'),
            self::WEEKLY => __('advanced-roster-for-filament::enums.repetition_pattern.description.weekly', [
                'weekday' => Weekday::from($baseDate->dayOfWeek)->getLabel(),
            ]),
            self::MONTHLY => __('advanced-roster-for-filament::enums.repetition_pattern.description.monthly', [
                'description' => $this->getMonthlyDescription($baseDate),
            ]),
            self::YEARLY => __('advanced-roster-for-filament::enums.repetition_pattern.description.yearly', [
                'date' => $baseDate->translatedFormat('d. F'),
            ]),
            self::CUSTOM => __('advanced-roster-for-filament::enums.repetition_pattern.description.custom'),
        };
    }

    private function getMonthlyDescription(CarbonInterface $baseDate): string
    {
        $weekOfMonth = (int) ceil($baseDate->day / 7);
        $ordinalNumbers = [
            1 => __('advanced-roster-for-filament::enums.repetition_pattern.ordinal.first'),
            2 => __('advanced-roster-for-filament::enums.repetition_pattern.ordinal.second'),
            3 => __('advanced-roster-for-filament::enums.repetition_pattern.ordinal.third'),
            4 => __('advanced-roster-for-filament::enums.repetition_pattern.ordinal.fourth'),
            5 => __('advanced-roster-for-filament::enums.repetition_pattern.ordinal.last'),
        ];

        $ordinal = $ordinalNumbers[$weekOfMonth] ?? $weekOfMonth.'.';
        $weekdayName = Weekday::from($baseDate->dayOfWeek)->getLabel();

        return $ordinal.' '.$weekdayName;
    }
}
