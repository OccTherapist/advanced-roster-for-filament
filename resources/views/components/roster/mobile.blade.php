@php
    use OccTherapist\AdvancedRosterForFilament\Support\RosterAssigneeResolver;
    use OccTherapist\AdvancedRosterForFilament\Support\RosterEntryTypeResolver;
    use OccTherapist\AdvancedRosterForFilament\Support\RosterPreferencesRepository;
    use OccTherapist\AdvancedRosterForFilament\Support\RosterScopeManager;
    use OccTherapist\AdvancedRosterForFilament\Support\RosterSectionRegistry;

    $sectionKey = config('advanced-roster-for-filament.assignee_section_key', 'assignees');
    $notesEnabled = config('advanced-roster-for-filament.features.notes', true);
    $assigneeResolver = app(RosterAssigneeResolver::class);
    $entryTypeResolver = app(RosterEntryTypeResolver::class);

    $sectionLabel = app(RosterSectionRegistry::class)->has($sectionKey)
        ? app(RosterSectionRegistry::class)->get($sectionKey)->getLabel()
        : __('advanced-roster-for-filament::navigation.label');
@endphp

<div class="lg:hidden flex flex-col gap-4">
    <div class="flex items-center flex-wrap justify-center gap-4 w-full">
        <div class="flex flex-col gap-1">
            <span class="font-bold text-lg">{{ $this->selectedDate->translatedFormat('l') }}</span>
            <span class="text-sm text-gray-500">{{ $this->selectedDate->format('d.m.Y') }}</span>
        </div>
        <div class="flex items-center gap-2">
            {{ $this->printAction() }}
            {{ $this->filtersAction() }}
            <x-filament::button wire:click="previousDay" key-bindings="arrowleft" color="gray" icon="heroicon-m-chevron-left" />
            <x-filament::button wire:click="today" key-bindings="t" color="gray">
                {{ __('advanced-roster-for-filament::actions.today') }}
            </x-filament::button>
            {{ $this->goToDateAction() }}
            <x-filament::button wire:click="nextDay" key-bindings="arrowright" color="gray" icon="heroicon-m-chevron-right" icon-position="after" />
        </div>
    </div>

    @if ($notesEnabled)
        @php
            $dayNotes = $this->rosterNotes->filter(
                fn ($note) => $note->date->toDateString() === $this->selectedDate->toDateString(),
            );
        @endphp

        @foreach ($dayNotes as $rosterNote)
            <div
                @class([
                    'px-4 py-3 rounded-xl shadow-sm border cursor-pointer text-sm text-center',
                    'bg-info-50 text-info-700 dark:bg-info-500/10 dark:text-info-400 border-info-200 dark:border-info-500/20' => ! ($rosterNote->color['value'] ?? null),
                ])
                @if ($rosterNote->color['value'] ?? null)
                    style="background-color: {{ $rosterNote->color['value'] }}; color: white; border-color: {{ $rosterNote->color['value'] }};"
                @endif
                wire:click="mountAction('manageRosterNote', { date: '{{ $this->selectedDate->toDateString() }}', id: {{ $rosterNote->id }} })"
            >{{ $rosterNote->note }}</div>
        @endforeach

        <div class="flex justify-center">
            <x-filament::button
                wire:click="mountAction('manageRosterNote', { date: '{{ $this->selectedDate->toDateString() }}' })"
                color="gray"
                icon="heroicon-m-plus"
                size="xs"
            >
                {{ __('advanced-roster-for-filament::actions.add_note') }}
            </x-filament::button>
        </div>
    @endif

    <div class="flex flex-col gap-3">
        <div class="px-4 py-2 bg-gray-50 dark:bg-white/5 rounded-lg text-center">
            <span class="font-bold text-xs uppercase tracking-wider text-gray-500">{{ $sectionLabel }}</span>
        </div>

        @foreach ($this->assignees as $assignee)
            @php
                $assigneeType = $assignee->getMorphClass();
                $assigneeId = $assignee->getKey();
                $assigneeUrl = method_exists($assignee, 'getRosterUrl') ? $assignee->getRosterUrl() : null;
                $dateString = $this->selectedDate->toDateString();
                $entries = $this->rosterEntries->filter(
                    fn ($entry) => $entry->assignee_type === $assigneeType
                        && (string) $entry->assignee_id === (string) $assigneeId
                        && $entry->start_at->toDateString() === $dateString,
                );
                $dailyMinutes = $entries
                    ->filter(function ($entry) use ($entryTypeResolver) {
                        $type = $entryTypeResolver->resolve($entry->entry_type);

                        return $type && ! $type->isAllDay();
                    })
                    ->sum(fn ($entry) => $entry->start_at->diffInMinutes($entry->end_at));
            @endphp
            <div
                wire:key="mobile-assignee-{{ $assigneeType }}-{{ $assigneeId }}-{{ $loop->index }}"
                class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-white/10 overflow-hidden"
            >
                <div class="px-4 py-3 border-b border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/5 flex justify-between items-center">
                    @if ($assigneeUrl)
                        <a class="font-medium text-sm hover:text-primary-500" href="{{ $assigneeUrl }}">
                            {{ $assigneeResolver->getAssigneeLabel($assignee) }}
                        </a>
                    @else
                        <span class="font-medium text-sm">{{ $assigneeResolver->getAssigneeLabel($assignee) }}</span>
                    @endif
                </div>
                <div class="p-2">
                    <x-advanced-roster::roster-day
                        :entries="$entries"
                        :date-string="$dateString"
                        :day="$this->selectedDate"
                        :assignee="$assignee"
                        :assignee-type="$assigneeType"
                        :assignee-id="$assigneeId"
                        :section-key="$sectionKey"
                        :daily-minutes="$dailyMinutes"
                    />
                </div>
            </div>
        @endforeach
    </div>
</div>
