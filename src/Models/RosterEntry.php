<?php

namespace OccTherapist\AdvancedRosterForFilament\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterScope;
use OccTherapist\AdvancedRosterForFilament\Enums\RepetitionInterval;
use OccTherapist\AdvancedRosterForFilament\Enums\RepetitionPattern;

class RosterEntry extends Model
{
    protected $fillable = [
        'scope_type',
        'scope_id',
        'section_key',
        'assignee_type',
        'assignee_id',
        'entry_type',
        'start_at',
        'end_at',
        'color',
        'comment',
        'parent_id',
        'repetition_pattern',
        'repetition_value',
        'repetition_interval',
        'repetition_weekdays',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'color' => 'array',
            'repetition_pattern' => RepetitionPattern::class,
            'repetition_interval' => RepetitionInterval::class,
            'repetition_weekdays' => 'array',
        ];
    }

    public function scope(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignee(): MorphTo
    {
        return $this->morphTo();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'), 'created_by');
    }

    #[Scope]
    public function forScope(Builder $query, RosterScope $scope): Builder
    {
        return $query
            ->where('scope_type', $scope->getRosterScopeType())
            ->where('scope_id', $scope->getRosterScopeKey());
    }
}
