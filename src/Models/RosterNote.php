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

class RosterNote extends Model
{
    protected $fillable = [
        'scope_type',
        'scope_id',
        'date',
        'note',
        'color',
        'parent_id',
        'repetition_pattern',
        'repetition_value',
        'repetition_interval',
        'repetition_weekdays',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    #[Scope]
    public function forScope(Builder $query, RosterScope $scope): Builder
    {
        return $query
            ->where('scope_type', $scope->getRosterScopeType())
            ->where('scope_id', $scope->getRosterScopeKey());
    }
}
