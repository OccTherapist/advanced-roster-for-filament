<?php

namespace OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use OccTherapist\AdvancedRosterForFilament\Enums\RepetitionInterval;
use OccTherapist\AdvancedRosterForFilament\Enums\RepetitionPattern;
use OccTherapist\AdvancedRosterForFilament\Enums\Weekday;

trait HasRepetitionManagement
{
    protected function generateRepetitionDates(CarbonInterface $baseDate, array $data, bool $skipWeekends = true): array
    {
        $pattern = $data['repetition_pattern'] ?? RepetitionPattern::NONE;

        if (! $pattern instanceof RepetitionPattern) {
            $pattern = RepetitionPattern::from($pattern);
        }

        if ($pattern === RepetitionPattern::NONE) {
            return [$baseDate];
        }

        $until = Carbon::parse($data['repetition_until'])->endOfDay();

        return match ($pattern) {
            RepetitionPattern::WEEKDAYS => $this->generateWeekdaysDates($baseDate, $until),
            RepetitionPattern::WEEKLY => $this->generateWeeklyDates($baseDate, $until, $skipWeekends),
            RepetitionPattern::MONTHLY => $this->generateMonthlyDates($baseDate, $until, $skipWeekends),
            RepetitionPattern::YEARLY => $this->generateYearlyDates($baseDate, $until, $skipWeekends),
            RepetitionPattern::CUSTOM => $this->generateCustomDates($baseDate, $until, $data, $skipWeekends),
            default => [$baseDate],
        };
    }

    protected function generateWeekdaysDates(CarbonInterface $baseDate, CarbonInterface $until): array
    {
        $dates = [];
        $current = $baseDate->copy();

        while ($current->lte($until)) {
            if ($current->isWeekday()) {
                $dates[] = $current->copy();
            }
            $current = $current->addDay();
        }

        return $dates;
    }

    protected function generateWeeklyDates(CarbonInterface $baseDate, CarbonInterface $until, bool $skipWeekends = true): array
    {
        $dates = [$baseDate];
        $current = $baseDate->copy();

        while (true) {
            $current = $current->addWeek();

            if ($current->isAfter($until)) {
                break;
            }

            if ($skipWeekends && $current->isWeekend()) {
                continue;
            }

            $dates[] = $current->copy();
        }

        return $dates;
    }

    protected function generateMonthlyDates(CarbonInterface $baseDate, CarbonInterface $until, bool $skipWeekends = true): array
    {
        $dates = [$baseDate];
        $current = $baseDate->copy();
        $weekOfMonth = (int) ceil($baseDate->day / 7);
        $dayOfWeek = $baseDate->dayOfWeek;

        while (true) {
            $current = $current->addMonth()->startOfMonth();

            $monthlyDate = $this->findNthWeekdayInMonth($current, $weekOfMonth, $dayOfWeek);

            if ($monthlyDate && $monthlyDate->isAfter($until)) {
                break;
            }

            if ($skipWeekends && $current->isWeekend()) {
                continue;
            }

            $dates[] = $monthlyDate;
        }

        return $dates;
    }

    protected function generateYearlyDates(CarbonInterface $baseDate, CarbonInterface $until, bool $skipWeekends = true): array
    {
        $dates = [$baseDate];
        $current = $baseDate->copy();

        while (true) {
            $current = $current->addYear();

            if ($current->isAfter($until)) {
                break;
            }

            if ($skipWeekends && $current->isWeekend()) {
                continue;
            }

            $dates[] = $current->copy();
        }

        return $dates;
    }

    protected function generateCustomDates(CarbonInterface $baseDate, CarbonInterface $until, array $data, bool $skipWeekends = true): array
    {
        $dates = [$baseDate];

        $interval = $data['repetition_interval'] ?? RepetitionInterval::WEEK;

        if (! $interval instanceof RepetitionInterval) {
            $interval = RepetitionInterval::from($interval);
        }

        $value = (int) ($data['repetition_value'] ?? 1);

        $weekdays = array_map(
            fn ($weekday) => $weekday instanceof Weekday
                ? $weekday->value
                : $weekday,
            $data['repetition_weekdays'] ?? []
        );

        $current = $baseDate->copy();

        while (true) {
            $current = $interval->applyOn($current, $value);

            if ($current->isAfter($until)) {
                break;
            }

            if ($skipWeekends && $current->isWeekend()) {
                continue;
            }

            if ($interval->is(RepetitionInterval::WEEK) && ! empty($weekdays) && ! in_array($current->dayOfWeek, $weekdays)) {
                continue;
            }

            $dates[] = $current->copy();
        }

        return $dates;
    }

    protected function findNthWeekdayInMonth(CarbonInterface $monthStart, int $weekOfMonth, int $dayOfWeek): ?CarbonInterface
    {
        $current = $monthStart->copy();

        while ($current->dayOfWeek !== $dayOfWeek && $current->month === $monthStart->month) {
            $current = $current->addDay();
        }

        if ($current->month !== $monthStart->month) {
            return null;
        }

        $firstOccurrence = $current->copy();

        if ($weekOfMonth === 5) {
            $lastDay = $monthStart->copy()->endOfMonth();

            while ($lastDay->dayOfWeek !== $dayOfWeek && $lastDay->month === $monthStart->month) {
                $lastDay = $lastDay->subDay();
            }

            return $lastDay->month === $monthStart->month
                ? $lastDay
                : null;
        }

        $targetDate = $firstOccurrence->addWeeks($weekOfMonth - 1);

        return $targetDate->month === $monthStart->month
            ? $targetDate
            : null;
    }
}
