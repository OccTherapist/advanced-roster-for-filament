<?php

namespace OccTherapist\AdvancedRosterForFilament\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterEntryTypeContract;

enum RosterEntryType: string implements HasColor, HasIcon, HasLabel, RosterEntryTypeContract
{
    case WORK = 'work';
    case SICK = 'sick';
    case VACATION = 'vacation';
    case UNAVAILABLE = 'unavailable';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::WORK => __('advanced-roster-for-filament::enums.entry_type.work'),
            self::SICK => __('advanced-roster-for-filament::enums.entry_type.sick'),
            self::VACATION => __('advanced-roster-for-filament::enums.entry_type.vacation'),
            self::UNAVAILABLE => __('advanced-roster-for-filament::enums.entry_type.unavailable'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::WORK => 'primary',
            self::SICK => 'danger',
            self::VACATION => 'info',
            self::UNAVAILABLE => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::WORK => 'heroicon-m-clock',
            self::SICK => 'heroicon-m-heart',
            self::VACATION => 'heroicon-m-sun',
            self::UNAVAILABLE => 'heroicon-m-no-symbol',
        };
    }

    public function isAllDay(): bool
    {
        return match ($this) {
            self::WORK => false,
            self::SICK, self::VACATION, self::UNAVAILABLE => true,
        };
    }

    public function replacesConflictingEntries(): bool
    {
        return $this->isAllDay() && $this !== self::WORK;
    }
}
