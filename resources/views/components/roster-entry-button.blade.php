@props([
    'id' => null,
    'label' => null,
    'comment' => null,
    'color' => 'primary',
    'bg' => null,
    'date' => null,
    'assigneeType' => null,
    'assigneeId' => null,
    'sectionKey' => null,
    'allowDrag' => false,
    'type' => 'edit',
])

@php
    use OccTherapist\AdvancedRosterForFilament\Support\RosterPreferencesRepository;
    use OccTherapist\AdvancedRosterForFilament\Support\RosterScopeManager;

    $settings = app(RosterPreferencesRepository::class)->get(
        app(RosterScopeManager::class)->resolve(),
    );
    $textSize = $settings['text_size'] ?? 'md';

    $textClasses = match ($textSize) {
        'sm' => ['base' => 'text-xs', 'small' => 'text-[10px]', 'tiny' => 'text-[8px]'],
        'lg' => ['base' => 'text-base', 'small' => 'text-sm', 'tiny' => 'text-[10px]'],
        default => ['base' => 'text-sm', 'small' => 'text-xs', 'tiny' => 'text-[10px]'],
    };

    $iconSizes = match ($textSize) {
        'sm' => 'w-3 h-3',
        'lg' => 'w-5 h-5',
        default => 'w-4 h-4',
    };

    $isSpecialColor = in_array($color, ['danger', 'info', 'warning']);

    $buttonClasses = match ($color) {
        'danger', 'info', 'warning' => "group w-full py-1 rounded font-semibold uppercase transition hover:opacity-75 flex items-center justify-center gap-1 {$textClasses['base']} " .
            match ($color) {
                'danger' => 'bg-danger-100 dark:bg-danger-500/20 text-danger-700 dark:text-danger-400',
                'info' => 'bg-info-100 dark:bg-info-500/20 text-info-700 dark:text-info-400',
                'warning' => ($bg ? '' : 'bg-warning-100 dark:bg-warning-500/20') . ' text-warning-700 dark:text-warning-400 flex-col px-2',
            },
        'gray' => "group relative p-1 rounded border leading-tight text-center font-semibold transition hover:opacity-75 flex items-center justify-center gap-1 bg-gray-50 border-gray-200 text-gray-600 dark:bg-white/5 dark:border-white/10 dark:text-gray-400 {$textClasses['base']}",
        'plus' => 'group rounded text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 p-1 flex justify-center items-center hover:bg-gray-50 border-gray-200 dark:hover:bg-white/5 dark:border-white/10 transition-colors',
        default => "group relative p-1 rounded border leading-tight text-center font-semibold transition hover:opacity-75 flex items-center justify-center gap-1 bg-primary-50 border-primary-200 text-primary-700 dark:bg-primary-500/10 dark:border-primary-500/20 dark:text-primary-400 {$textClasses['base']}",
    };

    $wireClick = $type === 'edit'
        ? "mountAction('editRosterEntry', { id: {$id} })"
        : "mountAction('addRosterEntry', " . json_encode([
            'date' => $date,
            'assignee_type' => $assigneeType,
            'assignee_id' => $assigneeId,
            'section_key' => $sectionKey,
        ]) . ')';

    $isDraggable = $type === 'edit' && $id && (! $isSpecialColor || $allowDrag);
@endphp

<button
    wire:click="{{ $wireClick }}"
    {{ $attributes->merge(['class' => $buttonClasses]) }}
    @if ($comment)
        x-tooltip="{ content: @js($comment), theme: $store.theme }"
    @endif
    @if ($bg)
        @style([
            "background-color: {$bg['value']}" => $bg['value'] ?? null,
            'color: white' => ($bg['value'] ?? null),
        ])
    @endif
    @if ($isDraggable)
        draggable="true"
        data-entry-id="{{ $id }}"
        data-assignee-type="{{ $assigneeType }}"
        data-assignee-id="{{ $assigneeId }}"
        data-date="{{ $date }}"
        ondragstart="handleDragStart(event)"
        onmousedown="this.style.cursor='grabbing'"
        onmouseup="this.style.cursor='grab'"
    @endif
>
    @if ($color === 'plus')
        <x-filament::icon icon="heroicon-m-plus" :class="$iconSizes" />
    @else
        <div class="flex flex-col items-center gap-1">
            @if ($color === 'warning')
                <span @class(['leading-none opacity-70', $textClasses['tiny']])>{{ $label }}</span>
                @if ($comment)
                    <span @class(['font-bold normal-case text-center break-words w-full', $textClasses['small']])>{{ $comment }}</span>
                @endif
            @else
                <div class="flex items-center gap-1">
                    {{ $label }}
                    @if ($comment)
                        <x-filament::icon icon="heroicon-m-chat-bubble-left-ellipsis" class="opacity-50 {{ $iconSizes }}" />
                    @endif
                </div>
            @endif
        </div>

        @if ($isDraggable)
            <x-filament::icon
                icon="heroicon-m-arrows-pointing-out"
                class="absolute right-1 {{ $iconSizes }} ml-1 opacity-0 group-hover:opacity-50 transition-opacity"
                style="cursor: grab;"
            />
        @endif
    @endif
</button>
