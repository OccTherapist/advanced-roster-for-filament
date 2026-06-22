<?php

namespace OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Concerns;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Livewire\Attributes\Computed;

trait HasCalenderNavigation
{
    public CarbonImmutable $currentDate;

    public CarbonImmutable $selectedDate;

    protected function weekStartDay(): int
    {
        return match (config('advanced-roster-for-filament.week_starts_at', 'monday')) {
            'sunday' => CarbonImmutable::SUNDAY,
            'saturday' => CarbonImmutable::SATURDAY,
            'friday' => CarbonImmutable::FRIDAY,
            'thursday' => CarbonImmutable::THURSDAY,
            'wednesday' => CarbonImmutable::WEDNESDAY,
            'tuesday' => CarbonImmutable::TUESDAY,
            default => CarbonImmutable::MONDAY,
        };
    }

    #[Computed]
    public function days(): array
    {
        $weekDays = (int) config('advanced-roster-for-filament.week_days', 5) - 1;

        return CarbonPeriod::create($this->currentDate, $this->currentDate->addDays($weekDays))->toArray();
    }

    public function previousWeek(): void
    {
        $this->currentDate = $this->currentDate->subWeek();
        $this->resetComputedProperties();
    }

    public function nextWeek(): void
    {
        $this->currentDate = $this->currentDate->addWeek();
        $this->resetComputedProperties();
    }

    public function today(): void
    {
        $this->currentDate = CarbonImmutable::now()->startOfWeek($this->weekStartDay());
        $this->selectedDate = CarbonImmutable::now();
        $this->resetComputedProperties();
    }

    public function previousDay(): void
    {
        $this->selectedDate = $this->selectedDate->subDay();

        while ($this->selectedDate->isWeekend()) {
            $this->selectedDate = $this->selectedDate->subDay();
        }

        $this->currentDate = $this->selectedDate->startOfWeek($this->weekStartDay());
        $this->resetComputedProperties();
    }

    public function nextDay(): void
    {
        $this->selectedDate = $this->selectedDate->addDay();

        while ($this->selectedDate->isWeekend()) {
            $this->selectedDate = $this->selectedDate->addDay();
        }

        $this->currentDate = $this->selectedDate->startOfWeek($this->weekStartDay());
        $this->resetComputedProperties();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = CarbonImmutable::parse($date);
        $this->currentDate = $this->selectedDate->startOfWeek($this->weekStartDay());
        $this->resetComputedProperties();
    }
}
