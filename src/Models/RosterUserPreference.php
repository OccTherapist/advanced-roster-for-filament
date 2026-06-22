<?php

namespace OccTherapist\AdvancedRosterForFilament\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterScope;

class RosterUserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'scope_type',
        'scope_id',
        'preferences',
    ];

    protected function casts(): array
    {
        return [
            'preferences' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'));
    }

    public function scope(): MorphTo
    {
        return $this->morphTo();
    }

    #[Scope]
    public function forScope(Builder $query, RosterScope $scope): Builder
    {
        return $query
            ->where('scope_type', $scope->getRosterScopeType())
            ->where('scope_id', $scope->getRosterScopeKey());
    }
}
