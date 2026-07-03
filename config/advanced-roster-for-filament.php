<?php

use App\Models\User;
use OccTherapist\AdvancedRosterForFilament\Enums\RosterEntryType;
use OccTherapist\AdvancedRosterForFilament\Support\FilamentRosterScopeResolver;
use OccTherapist\AdvancedRosterForFilament\Support\Filters\AssigneeRosterFilter;
use OccTherapist\AdvancedRosterForFilament\Support\Validators\OverlapRosterEntryValidator;

return [

    /*
    |--------------------------------------------------------------------------
    | Assignee model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model used as roster assignees in the default section.
    |
    */

    'assignee_model' => User::class,

    'assignee_name_column' => 'name',

    /*
    |--------------------------------------------------------------------------
    | Default assignee section key
    |--------------------------------------------------------------------------
    */

    'assignee_section_key' => 'assignees',

    /*
    |--------------------------------------------------------------------------
    | Scope resolver
    |--------------------------------------------------------------------------
    |
    | Resolves the current roster scope (e.g. Filament tenant).
    |
    */

    'scope_resolver' => FilamentRosterScopeResolver::class,

    /*
    |--------------------------------------------------------------------------
    | Roster sections
    |--------------------------------------------------------------------------
    |
    | Registered roster sections. The default assignee section is always
    | available; additional sections can be registered for future use.
    |
    */

    'sections' => [
        // 'assignees' => OccTherapist\AdvancedRosterForFilament\Support\ConfigRosterSection::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Entry types
    |--------------------------------------------------------------------------
    |
    | Built-in entry types. Custom types may implement
    | OccTherapist\AdvancedRosterForFilament\Contracts\RosterEntryTypeContract.
    |
    */

    'entry_types' => [
        RosterEntryType::WORK->value => RosterEntryType::class,
        RosterEntryType::SICK->value => RosterEntryType::class,
        RosterEntryType::VACATION->value => RosterEntryType::class,
        RosterEntryType::UNAVAILABLE->value => RosterEntryType::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Entry validators
    |--------------------------------------------------------------------------
    */

    'validators' => [
        OverlapRosterEntryValidator::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Roster filters
    |--------------------------------------------------------------------------
    |
    | Register RosterFilter implementations. The built-in assignee filter lets
    | users choose which rows are visible. Add custom filters for role,
    | location, or other criteria in your app.
    |
    */

    'filters' => [
        AssigneeRosterFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Overlap validation
    |--------------------------------------------------------------------------
    */

    'validate_overlap' => true,

    /*
    |--------------------------------------------------------------------------
    | Calendar
    |--------------------------------------------------------------------------
    |
    | Number of weekdays shown in the roster grid (default: Mon–Fri).
    |
    */

    'week_starts_at' => 'monday',

    'week_days' => 5,

    /*
    |--------------------------------------------------------------------------
    | Default times
    |--------------------------------------------------------------------------
    */

    'default_start_time' => '08:00',
    'default_end_time' => '09:00',

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    */

    'features' => [
        'notes' => true,
        'print' => true,
        'filters' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    'navigation' => [
        'group' => null,
        'icon' => 'heroicon-o-calendar-days',
        'label' => null,
        'sort' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Print / PDF
    |--------------------------------------------------------------------------
    */

    'print' => [
        'route_name' => 'advanced-roster.print',
        'view' => 'advanced-roster-for-filament::print',
        'pdf_view' => 'advanced-roster-for-filament::print',
    ],

];
