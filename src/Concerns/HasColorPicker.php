<?php

namespace OccTherapist\AdvancedRosterForFilament\Concerns;

use Filament\Forms\Components\Select;
use Filament\Support\Colors\Color;

trait HasColorPicker
{
    protected static function getColorPicker(): Select
    {
        $options = [
            'blue' => __('advanced-roster-for-filament::colors.blue'),
            'indigo' => __('advanced-roster-for-filament::colors.indigo'),
            'purple' => __('advanced-roster-for-filament::colors.purple'),
            'pink' => __('advanced-roster-for-filament::colors.pink'),
            'red' => __('advanced-roster-for-filament::colors.red'),
            'orange' => __('advanced-roster-for-filament::colors.orange'),
            'yellow' => __('advanced-roster-for-filament::colors.yellow'),
            'green' => __('advanced-roster-for-filament::colors.green'),
            'emerald' => __('advanced-roster-for-filament::colors.emerald'),
            'teal' => __('advanced-roster-for-filament::colors.teal'),
            'cyan' => __('advanced-roster-for-filament::colors.cyan'),
            'gray' => __('advanced-roster-for-filament::colors.gray'),
            'stone' => __('advanced-roster-for-filament::colors.stone'),
            'zinc' => __('advanced-roster-for-filament::colors.zinc'),
            'neutral' => __('advanced-roster-for-filament::colors.neutral'),
        ];

        return Select::make('color')
            ->label(__('advanced-roster-for-filament::fields.color'))
            ->options($options)
            ->native(false)
            ->searchable()
            ->dehydrateStateUsing(function (?string $state): ?array {
                if (! $state) {
                    return null;
                }

                $palette = [
                    'blue' => Color::Blue,
                    'indigo' => Color::Indigo,
                    'purple' => Color::Purple,
                    'pink' => Color::Pink,
                    'red' => Color::Red,
                    'orange' => Color::Orange,
                    'yellow' => Color::Yellow,
                    'green' => Color::Green,
                    'emerald' => Color::Emerald,
                    'teal' => Color::Teal,
                    'cyan' => Color::Cyan,
                    'gray' => Color::Gray,
                    'stone' => Color::Stone,
                    'zinc' => Color::Zinc,
                    'neutral' => Color::Neutral,
                ];

                $shades = [
                    'blue' => 700,
                    'indigo' => 700,
                    'purple' => 700,
                    'pink' => 700,
                    'red' => 700,
                    'orange' => 800,
                    'yellow' => 800,
                    'green' => 700,
                    'emerald' => 700,
                    'teal' => 700,
                    'cyan' => 700,
                    'gray' => 700,
                    'stone' => 700,
                    'zinc' => 700,
                    'neutral' => 800,
                ];

                if (! isset($palette[$state])) {
                    return null;
                }

                return [
                    'key' => $state,
                    'hex' => $palette[$state][$shades[$state] ?? 700] ?? $palette[$state][700],
                ];
            })
            ->formatStateUsing(function ($state): ?string {
                if (is_array($state)) {
                    return $state['key'] ?? null;
                }

                return $state;
            });
    }
}
