<?php

namespace OccTherapist\AdvancedRosterForFilament\Filament\Pages\RosterPage\Concerns;

use Carbon\CarbonPeriod;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use OccTherapist\AdvancedRosterForFilament\Models\RosterEntry;
use OccTherapist\AdvancedRosterForFilament\Models\RosterNote;
use OccTherapist\AdvancedRosterForFilament\Support\RosterAssigneeResolver;
use OccTherapist\AdvancedRosterForFilament\Support\RosterPreferencesRepository;
use OccTherapist\AdvancedRosterForFilament\Support\RosterScopeManager;
use Spatie\LaravelPdf\Facades\Pdf;

trait HasUtilityActions
{
    public function infoAction(): Action
    {
        return Action::make('info')
            ->label(__('advanced-roster-for-filament::actions.info'))
            ->hiddenLabel()
            ->icon(Heroicon::InformationCircle)
            ->color('info')
            ->tooltip(__('advanced-roster-for-filament::tooltips.info'))
            ->keyBindings('alt+i')
            ->slideOver(false)
            ->modalWidth(Width::FourExtraLarge)
            ->modalHeading(__('advanced-roster-for-filament::help.title'))
            ->modalContent(view('advanced-roster-for-filament::pages.info-modal'))
            ->modalSubmitAction(false)
            ->modalCancelAction(false);
    }

    public function settingsAction(): Action
    {
        return Action::make('settings')
            ->label(__('advanced-roster-for-filament::actions.settings'))
            ->hiddenLabel()
            ->icon(Heroicon::Cog6Tooth)
            ->color('gray')
            ->tooltip(__('advanced-roster-for-filament::actions.settings'))
            ->keyBindings('alt+s')
            ->modalHeading(__('advanced-roster-for-filament::messages.settings_heading'))
            ->modalSubmitActionLabel(__('advanced-roster-for-filament::actions.save'))
            ->modalWidth(Width::ExtraLarge)
            ->fillForm(fn () => app(RosterPreferencesRepository::class)->get(
                app(RosterScopeManager::class)->resolve(),
            ))
            ->schema([
                ToggleButtons::make('layout')
                    ->label(__('advanced-roster-for-filament::fields.layout'))
                    ->options([
                        'compact' => __('advanced-roster-for-filament::layout.compact'),
                        'relaxed' => __('advanced-roster-for-filament::layout.relaxed'),
                    ])
                    ->tooltips([
                        'compact' => __('advanced-roster-for-filament::tooltips.layout_compact'),
                        'relaxed' => __('advanced-roster-for-filament::tooltips.layout_relaxed'),
                    ])
                    ->inline()
                    ->required()
                    ->live(),

                ToggleButtons::make('text_size')
                    ->label(__('advanced-roster-for-filament::fields.text_size'))
                    ->options([
                        'sm' => __('advanced-roster-for-filament::text_size.sm'),
                        'md' => __('advanced-roster-for-filament::text_size.md'),
                        'lg' => __('advanced-roster-for-filament::text_size.lg'),
                    ])
                    ->inline()
                    ->required()
                    ->live(),

                Fieldset::make(__('advanced-roster-for-filament::actions.reset_assignee_order'))
                    ->schema([
                        Action::make('resetAssigneeOrder')
                            ->label(__('advanced-roster-for-filament::actions.reset_assignee_order'))
                            ->color('danger')
                            ->link()
                            ->icon(Heroicon::ArrowPath)
                            ->action(fn () => $this->resetAssigneeOrder())
                            ->requiresConfirmation()
                            ->slideOver(false)
                            ->modalHeading(__('advanced-roster-for-filament::messages.reset_order_heading'))
                            ->modalDescription(__('advanced-roster-for-filament::messages.reset_order_description'))
                            ->modalSubmitActionLabel(__('advanced-roster-for-filament::messages.reset_order_submit')),
                    ])
                    ->extraAttributes(['class' => 'border border-danger-500 p-4 rounded-lg flex justify-start']),
            ])
            ->action(function (array $data) {
                app(RosterPreferencesRepository::class)->save(
                    $data,
                    app(RosterScopeManager::class)->resolve(),
                );

                Notification::make()
                    ->title(__('advanced-roster-for-filament::messages.settings_saved'))
                    ->success()
                    ->send();
            });
    }

    public function printAction(): Action
    {
        return Action::make('print')
            ->label(__('advanced-roster-for-filament::actions.print'))
            ->hiddenLabel()
            ->tooltip(__('advanced-roster-for-filament::actions.print'))
            ->keyBindings('alt+p')
            ->icon(Heroicon::Printer)
            ->color('gray')
            ->slideOver(false)
            ->modalHeading(__('advanced-roster-for-filament::messages.print_heading'))
            ->modalWidth(Width::Medium)
            ->schema([
                ToggleButtons::make('weeks_count')
                    ->label(__('advanced-roster-for-filament::fields.weeks_count'))
                    ->options([
                        '1' => trans_choice('advanced-roster-for-filament::messages.week_option', 1, ['count' => 1]),
                        '2' => trans_choice('advanced-roster-for-filament::messages.week_option', 2, ['count' => 2]),
                        '3' => trans_choice('advanced-roster-for-filament::messages.week_option', 3, ['count' => 3]),
                        '4' => trans_choice('advanced-roster-for-filament::messages.week_option', 4, ['count' => 4]),
                    ])
                    ->default('1')
                    ->inline()
                    ->required(),

                CheckboxList::make('assignee_ids')
                    ->label(__('advanced-roster-for-filament::fields.assignee_ids'))
                    ->options(fn () => $this->assignees()->mapWithKeys(
                        fn ($assignee) => [
                            $assignee->getKey() => app(RosterAssigneeResolver::class)->getAssigneeLabel($assignee),
                        ]
                    ))
                    ->default(fn () => $this->assignees()->pluck('id')->toArray())
                    ->columns(2)
                    ->bulkToggleable(),
            ])
            ->action(function (array $data) {
                return $this->exportPrint($data);
            });
    }

    public function goToDateAction(): Action
    {
        return Action::make('goToDate')
            ->label(__('advanced-roster-for-filament::actions.go_to_date'))
            ->tooltip(__('advanced-roster-for-filament::actions.go_to_date'))
            ->keyBindings('alt+d')
            ->hiddenLabel()
            ->icon(Heroicon::Calendar)
            ->button()
            ->color('gray')
            ->slideOver(false)
            ->modalWidth(Width::ExtraSmall)
            ->modalSubmitActionLabel(__('advanced-roster-for-filament::actions.switch'))
            ->schema([
                DatePicker::make('date')
                    ->label(__('advanced-roster-for-filament::fields.date'))
                    ->required()
                    ->default($this->selectedDate->toDateString()),
            ])
            ->action(fn (array $data) => $this->selectDate($data['date']));
    }

    protected function exportPrint(array $data)
    {
        $weeksCount = (int) ($data['weeks_count'] ?? 1);
        $startOfFirstWeek = $this->currentDate->copy()->startOfWeek();
        $sectionKey = $this->sectionKey();
        $assigneeIds = $data['assignee_ids'] ?? [];
        $scope = app(RosterScopeManager::class)->require();
        $assigneeResolver = app(RosterAssigneeResolver::class);
        $assigneeModel = config('advanced-roster-for-filament.assignee_model');

        $assignees = $assigneeModel::query()
            ->whereIn('id', $assigneeIds)
            ->get();

        $assignees = $this->applySortOrder($assignees, 'assignee_order');

        $weekDays = (int) config('advanced-roster-for-filament.week_days', 5);
        $weeks = [];

        for ($i = 0; $i < $weeksCount; $i++) {
            $weekStart = $startOfFirstWeek->copy()->addWeeks($i);
            $weeks[] = [
                'start' => $weekStart,
                'days' => CarbonPeriod::create($weekStart, $weekStart->copy()->addDays($weekDays - 1))->toArray(),
            ];
        }

        $periodEnd = $startOfFirstWeek->copy()->addWeeks($weeksCount - 1)->addDays($weekDays - 1);

        $entries = RosterEntry::query()
            ->forScope($scope)
            ->where('section_key', $sectionKey)
            ->whereIn('assignee_id', $assigneeIds)
            ->whereBetween('start_at', [$startOfFirstWeek->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
            ->orderBy('start_at')
            ->get();

        $notes = collect();

        if (config('advanced-roster-for-filament.features.notes', true)) {
            $notes = RosterNote::query()
                ->forScope($scope)
                ->whereBetween('date', [$startOfFirstWeek->toDateString(), $periodEnd->toDateString()])
                ->orderBy('date')
                ->get();
        }

        $viewData = [
            'weeks' => $weeks,
            'assignees' => $assignees,
            'entries' => $entries,
            'notes' => $notes,
            'scope' => $scope,
            'assigneeResolver' => $assigneeResolver,
        ];

        if (class_exists(Pdf::class)) {
            $path = storage_path('app/tmp/roster_'.$startOfFirstWeek->format('Y-W').'.pdf');

            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            Pdf::view(
                config('advanced-roster-for-filament.print.pdf_view', 'advanced-roster-for-filament::print'),
                $viewData,
            )
                ->format('a4')
                ->name('roster_'.$startOfFirstWeek->format('Y-W').'.pdf')
                ->save($path);

            return response()->download($path);
        }

        return redirect()->route(
            config('advanced-roster-for-filament.print.route_name', 'advanced-roster.print'),
            [
                'start' => $startOfFirstWeek->toDateString(),
                'weeks' => $weeksCount,
                'assignees' => implode(',', $assigneeIds),
            ],
        );
    }
}
