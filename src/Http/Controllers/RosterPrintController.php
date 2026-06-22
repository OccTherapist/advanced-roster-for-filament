<?php

namespace OccTherapist\AdvancedRosterForFilament\Http\Controllers;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use OccTherapist\AdvancedRosterForFilament\Models\RosterEntry;
use OccTherapist\AdvancedRosterForFilament\Models\RosterNote;
use OccTherapist\AdvancedRosterForFilament\Support\RosterAssigneeResolver;
use OccTherapist\AdvancedRosterForFilament\Support\RosterPreferencesRepository;
use OccTherapist\AdvancedRosterForFilament\Support\RosterScopeManager;

class RosterPrintController extends Controller
{
    public function __invoke(
        Request $request,
        RosterScopeManager $scopeManager,
        RosterAssigneeResolver $assigneeResolver,
        RosterPreferencesRepository $preferences,
    ): View {
        $scope = $scopeManager->require();
        $weeksCount = max(1, (int) $request->query('weeks', 1));
        $startDate = CarbonImmutable::parse($request->query('start', now()->startOfWeek()->toDateString()));
        $sectionKey = config('advanced-roster-for-filament.assignee_section_key', 'assignees');
        $assigneeIds = collect(explode(',', (string) $request->query('assignees', '')))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $assignees = $assigneeResolver->getAssignees($sectionKey, $scope);

        if ($assigneeIds !== []) {
            $assignees = $assignees->whereIn('id', $assigneeIds)->values();
        }

        $userPreferences = $preferences->get($scope);
        $assigneeOrder = $userPreferences['assignee_order'] ?? null;

        if ($assigneeOrder) {
            $byId = $assignees->keyBy('id');
            $ordered = collect();

            foreach ($assigneeOrder as $id) {
                if ($byId->has($id)) {
                    $ordered->push($byId->get($id));
                }
            }

            $assignees = $ordered->concat($assignees->whereNotIn('id', $assigneeOrder));
        }

        $weekDays = (int) config('advanced-roster-for-filament.week_days', 5);
        $weeks = [];

        for ($i = 0; $i < $weeksCount; $i++) {
            $weekStart = $startDate->addWeeks($i);
            $weeks[] = [
                'start' => $weekStart,
                'days' => CarbonPeriod::create($weekStart, $weekStart->addDays($weekDays - 1))->toArray(),
            ];
        }

        $periodEnd = $startDate->addWeeks($weeksCount - 1)->addDays($weekDays - 1);

        $entries = RosterEntry::query()
            ->forScope($scope)
            ->where('section_key', $sectionKey)
            ->whereBetween('start_at', [$startDate->startOfDay(), $periodEnd->endOfDay()])
            ->orderBy('start_at')
            ->get();

        $notes = collect();

        if (config('advanced-roster-for-filament.features.notes', true)) {
            $notes = RosterNote::query()
                ->forScope($scope)
                ->whereBetween('date', [$startDate->toDateString(), $periodEnd->toDateString()])
                ->orderBy('date')
                ->get();
        }

        return view(config('advanced-roster-for-filament.print.view', 'advanced-roster-for-filament::print'), [
            'weeks' => $weeks,
            'assignees' => $assignees,
            'entries' => $entries,
            'notes' => $notes,
            'scope' => $scope,
            'assigneeResolver' => $assigneeResolver,
        ]);
    }
}
