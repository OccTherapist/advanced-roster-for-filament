@props([
    'entries',
    'dateString',
    'day',
    'assignee',
    'assigneeType',
    'assigneeId',
    'sectionKey',
    'dailyMinutes' => 0,
])

@php
    use OccTherapist\AdvancedRosterForFilament\Support\RosterEntryTypeResolver;
    use OccTherapist\AdvancedRosterForFilament\Support\RosterPreferencesRepository;
    use OccTherapist\AdvancedRosterForFilament\Support\RosterScopeManager;

    $settings = app(RosterPreferencesRepository::class)->get(
        app(RosterScopeManager::class)->resolve(),
    );
    $isCompact = ($settings['layout'] ?? 'relaxed') === 'compact';
    $textSize = $settings['text_size'] ?? 'md';
    $entryTypeResolver = app(RosterEntryTypeResolver::class);

    $baseTextClass = match ($textSize) {
        'sm' => 'text-xs',
        'lg' => 'text-base',
        default => 'text-sm',
    };

    $tinyTextClass = match ($textSize) {
        'sm' => 'text-[8px]',
        'lg' => 'text-[10px]',
        default => 'text-[8px]',
    };

    $specialEntry = null;
    $specialType = null;

    foreach ($entries as $entry) {
        $type = $entryTypeResolver->resolve($entry->entry_type);

        if ($type && $type->isAllDay()) {
            $specialEntry = $entry;
            $specialType = $type;
            break;
        }
    }

    $workEntries = $entries->filter(function ($entry) use ($entryTypeResolver) {
        $type = $entryTypeResolver->resolve($entry->entry_type);

        return $type && ! $type->isAllDay();
    });
@endphp

<div
    @class([
        'roster-day text-center align-top border-none border-gray-100 dark:border-white/5 roster-drop-zone',
        $isCompact ? 'px-0.5 py-0.5 min-h-[40px]' : 'px-1 py-1 min-h-[60px]',
    ])
    data-date="{{ $dateString }}"
    data-assignee-type="{{ $assigneeType }}"
    data-assignee-id="{{ $assigneeId }}"
    ondrop="handleDrop(event)"
    ondragover="handleDragOver(event)"
    ondragenter="handleDragEnter(event)"
    ondragleave="handleDragLeave(event)"
>
    @if ($specialType)
        <x-advanced-roster::roster-entry-button
            :id="$specialEntry->id"
            :label="$specialType->getLabel()"
            :comment="$specialEntry->comment"
            :color="$specialType->getColor()"
            :bg="$specialEntry->color"
            :date="$dateString"
            :assignee-type="$assigneeType"
            :assignee-id="$assigneeId"
            :section-key="$sectionKey"
            :allow-drag="$specialType->getKey() === 'unavailable'"
        />
    @else
        <div class="flex flex-col gap-1">
            @forelse ($workEntries as $entry)
                @php
                    $entryType = $entryTypeResolver->resolve($entry->entry_type);
                    $label = $entryType?->isAllDay()
                        ? $entryType->getLabel()
                        : $entry->start_at->format('H:i') . '-' . $entry->end_at->format('H:i');
                @endphp
                <x-advanced-roster::roster-entry-button
                    :id="$entry->id"
                    :label="$label"
                    :comment="$entry->comment"
                    :color="$day->isFuture() ? 'gray' : ($entryType?->getColor() ?? 'primary')"
                    :bg="$entry->color"
                    :date="$dateString"
                    :assignee-type="$assigneeType"
                    :assignee-id="$assigneeId"
                    :section-key="$sectionKey"
                />
            @empty
                <span @class(['text-gray-300 dark:text-gray-700 font-semibold uppercase tracking-wider contents', $tinyTextClass])>
                    {{ __('advanced-roster-for-filament::messages.no_entry') }}
                </span>
            @endforelse

            <x-advanced-roster::roster-entry-button
                type="add"
                :date="$dateString"
                :assignee-type="$assigneeType"
                :assignee-id="$assigneeId"
                :section-key="$sectionKey"
                color="plus"
            />
        </div>
    @endif
</div>
