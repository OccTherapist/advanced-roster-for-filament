<?php

namespace OccTherapist\AdvancedRosterForFilament\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Weekday: int implements HasColor, HasLabel
{
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;
    case Sunday = 0;

    public function getLabel(): string
    {
        return match ($this) {
            self::Monday => __('advanced-roster-for-filament::enums.weekday.monday'),
            self::Tuesday => __('advanced-roster-for-filament::enums.weekday.tuesday'),
            self::Wednesday => __('advanced-roster-for-filament::enums.weekday.wednesday'),
            self::Thursday => __('advanced-roster-for-filament::enums.weekday.thursday'),
            self::Friday => __('advanced-roster-for-filament::enums.weekday.friday'),
            self::Saturday => __('advanced-roster-for-filament::enums.weekday.saturday'),
            self::Sunday => __('advanced-roster-for-filament::enums.weekday.sunday'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Monday => 'monday',
            self::Tuesday => 'tuesday',
            self::Wednesday => 'wednesday',
            self::Thursday => 'thursday',
            self::Friday => 'friday',
            self::Saturday => 'saturday',
            self::Sunday => 'sunday',
        };
    }
}
