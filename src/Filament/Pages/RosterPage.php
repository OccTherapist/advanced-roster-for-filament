<?php

namespace OccTherapist\AdvancedRosterForFilament\Filament\Pages;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Concerns\HasCalenderNavigation;
use OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Concerns\HasDataSorting;
use OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Concerns\HasEntryManagement;
use OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Concerns\HasRepetitionManagement;
use OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Concerns\HasRosterData;
use OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Concerns\HasRosterEntryManagement;
use OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Concerns\HasRosterNotesManagement;
use OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Concerns\HasUtilityActions;

class RosterPage extends Page implements HasActions
{
    use HasCalenderNavigation;
    use HasDataSorting;
    use HasEntryManagement;
    use HasRepetitionManagement;
    use HasRosterData;
    use HasRosterEntryManagement;
    use HasRosterNotesManagement;
    use HasUtilityActions;
    use InteractsWithActions;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 100;

    protected static string $view = 'advanced-roster-for-filament::pages.roster';

    protected Width|string|null $maxContentWidth = Width::Full;

    public static function getNavigationLabel(): string
    {
        return config('advanced-roster-for-filament.navigation.label')
            ?? __('advanced-roster-for-filament::navigation.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('advanced-roster-for-filament.navigation.group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('advanced-roster-for-filament.navigation.sort', static::$navigationSort);
    }

    public function getTitle(): string|Htmlable
    {
        return __('advanced-roster-for-filament::navigation.title');
    }

    public function mount(): void
    {
        $this->today();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->infoAction(),
            $this->goToDateAction(),
            $this->settingsAction(),
            $this->printAction(),
        ];
    }

    protected function getActions(): array
    {
        $actions = [
            $this->addRosterEntryAction(),
            $this->editRosterEntryAction(),
            $this->chooseMoveOrCopyAction(),
            $this->chooseCopyOptionAction(),
            $this->chooseMoveOptionAction(),
        ];

        if (config('advanced-roster-for-filament.features.notes', true)) {
            $actions[] = $this->manageRosterNoteAction();
        }

        return $actions;
    }
}
