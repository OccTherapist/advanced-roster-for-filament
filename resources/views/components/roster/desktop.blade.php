@php
    use OccTherapist\AdvancedRosterForFilament\Support\RosterAssigneeResolver;
    use OccTherapist\AdvancedRosterForFilament\Support\RosterEntryTypeResolver;
    use OccTherapist\AdvancedRosterForFilament\Support\RosterPreferencesRepository;
    use OccTherapist\AdvancedRosterForFilament\Support\RosterScopeManager;
    use OccTherapist\AdvancedRosterForFilament\Support\RosterSectionRegistry;

    $settings = app(RosterPreferencesRepository::class)->get(
        app(RosterScopeManager::class)->resolve(),
    );
    $isCompact = ($settings['layout'] ?? 'relaxed') === 'compact';
    $textSize = $settings['text_size'] ?? 'md';
    $sectionKey = config('advanced-roster-for-filament.assignee_section_key', 'assignees');
    $notesEnabled = config('advanced-roster-for-filament.features.notes', true);
    $assigneeResolver = app(RosterAssigneeResolver::class);
    $entryTypeResolver = app(RosterEntryTypeResolver::class);
    $gridColumnCount = count($this->days) + 1;

    $sectionLabel = app(RosterSectionRegistry::class)->has($sectionKey)
        ? app(RosterSectionRegistry::class)->get($sectionKey)->getLabel()
        : __('advanced-roster-for-filament::navigation.label');

    $baseTextClass = match ($textSize) {
        'sm' => 'text-xs',
        'lg' => 'text-base',
        default => 'text-sm',
    };

    $smallTextClass = match ($textSize) {
        'sm' => 'text-[10px]',
        'lg' => 'text-sm',
        default => 'text-xs',
    };

    $tinyTextClass = match ($textSize) {
        'sm' => 'text-[8px]',
        'lg' => 'text-[10px]',
        default => 'text-[9px]',
    };

    $iconSizes = match ($textSize) {
        'sm' => 'w-3 h-3',
        'lg' => 'w-5 h-5',
        default => 'w-4 h-4',
    };

    $cellPadding = $isCompact ? 'px-2 py-1' : 'px-4 py-3';
    $headerPadding = $isCompact ? 'px-2 py-1' : 'px-4 py-2';

    $lastDay = collect($this->days)->last();
@endphp

<div class="hidden lg:block space-y-2">
    <div class="flex items-center justify-between gap-4 w-full">
        <div class="flex flex-col gap-2">
            <x-filament::badge color="gray">
                {{ __('advanced-roster-for-filament::messages.calendar_week', ['week' => $this->currentDate->format('W')]) }}
            </x-filament::badge>
            <span class="font-semibold">
                {{ $this->currentDate->format('d.m.Y') }} – {{ $lastDay?->format('d.m.Y') }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            {{ $this->printAction() }}
            <x-filament::button
                wire:click="previousWeek"
                :tooltip="__('advanced-roster-for-filament::tooltips.previous_week')"
                :key-bindings="['p', 'LEFT']"
                color="gray"
                icon="heroicon-m-chevron-left"
            />
            <x-filament::button
                wire:click="today"
                :tooltip="__('advanced-roster-for-filament::tooltips.today')"
                :key-bindings="['t', 'SPACE']"
                color="gray"
            >
                {{ __('advanced-roster-for-filament::actions.today') }}
            </x-filament::button>
            {{ $this->goToDateAction() }}
            <x-filament::button
                wire:click="nextWeek"
                :tooltip="__('advanced-roster-for-filament::tooltips.next_week')"
                :key-bindings="['n', 'RIGHT']"
                color="gray"
                icon="heroicon-m-chevron-right"
                icon-position="after"
            />
            {{ $this->settingsAction() }}
        </div>
    </div>

    <div class="overflow-x-auto bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-white/10">
        <div class="w-full text-left divide-y divide-gray-200 dark:divide-white/10">
            <div>
                <div
                    class="bg-gray-50 dark:bg-white/5 grid"
                    style="grid-template-columns: repeat({{ $gridColumnCount }}, minmax(0, 1fr));"
                >
                    <div @class([$cellPadding, $baseTextClass, 'font-semibold flex justify-center items-center'])>
                        {{ __('advanced-roster-for-filament::fields.name') }}
                    </div>
                    @foreach ($this->days as $day)
                        @php
                            $dayNotes = $this->rosterNotes->filter(
                                fn ($note) => $note->date->toDateString() === $day->toDateString(),
                            );
                        @endphp
                        <div @class([
                            $cellPadding,
                            $baseTextClass,
                            'font-semibold text-center relative group',
                            'bg-warning-500/10' => $day->isToday(),
                        ])>
                            <div class="flex flex-col h-full justify-between gap-1">
                                <div>
                                    {{ $day->translatedFormat('D') }}<br>
                                    <span @class([$smallTextClass, 'font-normal text-gray-500'])>{{ $day->format('d.m.') }}</span>
                                </div>

                                @if ($notesEnabled)
                                    <div class="flex flex-col gap-0.5">
                                        @foreach ($dayNotes as $rosterNote)
                                            <div
                                                @class([
                                                    $tinyTextClass,
                                                    'leading-tight font-normal p-1 rounded border cursor-pointer hover:opacity-80',
                                                    'bg-info-50 text-info-700 dark:bg-info-500/10 dark:text-info-400 border-info-200 dark:border-info-500/20' => ! ($rosterNote->color['value'] ?? null),
                                                ])
                                                @if ($rosterNote->color['value'] ?? null)
                                                    style="background-color: {{ $rosterNote->color['value'] }}; color: white; border-color: {{ $rosterNote->color['value'] }};"
                                                @endif
                                                wire:click="mountAction('manageRosterNote', { date: '{{ $day->toDateString() }}', id: {{ $rosterNote->id }} })"
                                            >{{ $rosterNote->note }}</div>
                                        @endforeach

                                        <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                            <span
                                                wire:click="mountAction('manageRosterNote', { date: '{{ $day->toDateString() }}' })"
                                                @class([
                                                    'inline-flex items-center gap-1 p-1 rounded bg-gray-500/10 cursor-pointer text-center text-gray-400 hover:text-primary-500',
                                                    $tinyTextClass,
                                                ])
                                            >
                                                <x-filament::icon icon="heroicon-m-plus" class="w-3 h-3" />
                                                {{ __('advanced-roster-for-filament::actions.add_note') }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div
                class="divide-y divide-gray-200 dark:divide-white/10 grid"
                style="grid-template-columns: repeat({{ $gridColumnCount }}, minmax(0, 1fr));"
            >
                @if (! $isCompact)
                    <div class="bg-gray-50/50 dark:bg-white/5" style="grid-column: 1 / -1;">
                        <div @class([$headerPadding, $smallTextClass, 'font-bold text-center tracking-wider text-gray-500'])>
                            {{ $sectionLabel }}
                        </div>
                    </div>
                @endif

                @foreach ($this->assignees as $assignee)
                    @php
                        $assigneeType = $assignee->getMorphClass();
                        $assigneeId = $assignee->getKey();
                        $assigneeUrl = method_exists($assignee, 'getRosterUrl') ? $assignee->getRosterUrl() : null;
                    @endphp
                    <div class="contents" wire:key="assignee-{{ $assigneeType }}-{{ $assigneeId }}-{{ $loop->index }}">
                        <div
                            @class([
                                $cellPadding,
                                $baseTextClass,
                                'font-medium flex justify-center items-center relative group roster-row roster-assignee-row',
                                'bg-gray-300/10 dark:bg-gray-800/10' => $loop->index % 2 === 0,
                            ])
                            data-assignee-id="{{ $assigneeId }}"
                            draggable="true"
                            ondragstart="handleAssigneeDragStart(event)"
                            ondragover="handleAssigneeDragOver(event)"
                            ondrop="handleAssigneeDrop(event)"
                        >
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity cursor-grab drag-handle text-gray-400 hover:text-gray-600">
                                <x-filament::icon icon="heroicon-s-bars-3" :class="$iconSizes" />
                            </div>
                            <div class="flex-1 flex justify-center items-center flex-col">
                                @if ($assigneeUrl)
                                    <a
                                        class="whitespace-nowrap hover:text-primary-500"
                                        title="{{ $assigneeResolver->getAssigneeLabel($assignee) }}"
                                        href="{{ $assigneeUrl }}"
                                    >{{ $assigneeResolver->getAssigneeLabel($assignee) }}</a>
                                @else
                                    <span>{{ $assigneeResolver->getAssigneeLabel($assignee) }}</span>
                                @endif
                            </div>
                        </div>

                        @foreach ($this->days as $day)
                            @php
                                $dateString = $day->toDateString();
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
                            <div @class([
                                'text-center align-top border-l border-gray-100 dark:border-white/5 roster-day-column',
                                'bg-gray-300/10 dark:bg-gray-800/10' => $loop->parent->index % 2 === 0,
                            ])>
                                <x-advanced-roster::roster-day
                                    :entries="$entries"
                                    :date-string="$dateString"
                                    :day="$day"
                                    :assignee="$assignee"
                                    :assignee-type="$assigneeType"
                                    :assignee-id="$assigneeId"
                                    :section-key="$sectionKey"
                                    :daily-minutes="$dailyMinutes"
                                />
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
