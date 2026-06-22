<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('advanced-roster-for-filament::navigation.title') }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; font-size: 12px; margin: 1rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .note { background: #fffbeb; margin-bottom: 4px; padding: 2px 4px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <p class="no-print">
        <button onclick="window.print()">{{ __('advanced-roster-for-filament::actions.print') }}</button>
    </p>

    <h1>{{ __('advanced-roster-for-filament::navigation.title') }}</h1>

    @foreach ($weeks as $week)
        <h2>{{ $week['start']->format('d.m.Y') }}</h2>
        <table>
            <thead>
                <tr>
                    <th>{{ __('advanced-roster-for-filament::fields.assignee_ids') }}</th>
                    @foreach ($week['days'] as $day)
                        <th>{{ $day->translatedFormat('D d.m.') }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($assignees as $assignee)
                    <tr>
                        <td>{{ $assigneeResolver->getAssigneeLabel($assignee) }}</td>
                        @foreach ($week['days'] as $day)
                            @php
                                $date = $day->toDateString();
                                $dayEntries = $entries->filter(
                                    fn ($entry) => (string) $entry->assignee_id === (string) $assignee->getKey()
                                        && $entry->start_at->toDateString() === $date
                                );
                            @endphp
                            <td>
                                @foreach ($dayEntries as $entry)
                                    <div>
                                        @if ($entry->start_at->format('H:i') !== '00:00' || $entry->end_at->format('H:i') !== '23:59')
                                            {{ $entry->start_at->format('H:i') }}–{{ $entry->end_at->format('H:i') }}
                                        @endif
                                        {{ $entry->entry_type }}
                                        @if ($entry->comment)
                                            <br><small>{{ $entry->comment }}</small>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($notes->isNotEmpty())
            <h3>{{ __('advanced-roster-for-filament::fields.note') }}</h3>
            @foreach ($week['days'] as $day)
                @php $dayNotes = $notes->filter(fn ($n) => $n->date->toDateString() === $day->toDateString()); @endphp
                @foreach ($dayNotes as $note)
                    <div class="note">{{ $day->format('d.m.') }}: {{ $note->note }}</div>
                @endforeach
            @endforeach
        @endif
    @endforeach
</body>
</html>
