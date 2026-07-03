<?php

namespace OccTherapist\AdvancedRosterForFilament\Support;

use Illuminate\Support\Facades\Auth;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterScope;
use OccTherapist\AdvancedRosterForFilament\Models\RosterUserPreference;

class RosterPreferencesRepository
{
    public function get(?RosterScope $scope = null, ?int $userId = null): array
    {
        $scope ??= app(RosterScopeManager::class)->resolve();

        if (! $scope) {
            return $this->defaults();
        }

        $userId ??= Auth::id();

        if (! $userId) {
            return $this->defaults();
        }

        $preference = RosterUserPreference::query()
            ->where('user_id', $userId)
            ->forScope($scope)
            ->first();

        return array_merge($this->defaults(), $preference?->preferences ?? []);
    }

    public function save(array $preferences, ?RosterScope $scope = null, ?int $userId = null): void
    {
        $scope ??= app(RosterScopeManager::class)->resolve();

        if (! $scope) {
            return;
        }

        $userId ??= Auth::id();

        if (! $userId) {
            return;
        }

        RosterUserPreference::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'scope_type' => $scope->getRosterScopeType(),
                'scope_id' => $scope->getRosterScopeKey(),
            ],
            [
                'preferences' => array_merge($this->get($scope, $userId), $preferences),
            ],
        );
    }

    public function resetAssigneeOrder(?RosterScope $scope = null, ?int $userId = null): void
    {
        $preferences = $this->get($scope, $userId);
        unset($preferences['assignee_order']);

        $this->overwrite($preferences, $scope, $userId);
    }

    public function resetFilters(?RosterScope $scope = null, ?int $userId = null): void
    {
        $preferences = $this->get($scope, $userId);
        unset($preferences['filters']);

        $this->overwrite($preferences, $scope, $userId);
    }

    public function overwrite(array $preferences, ?RosterScope $scope = null, ?int $userId = null): void
    {
        $scope ??= app(RosterScopeManager::class)->resolve();

        if (! $scope) {
            return;
        }

        $userId ??= Auth::id();

        if (! $userId) {
            return;
        }

        RosterUserPreference::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'scope_type' => $scope->getRosterScopeType(),
                'scope_id' => $scope->getRosterScopeKey(),
            ],
            [
                'preferences' => $preferences,
            ],
        );
    }

    public function defaults(): array
    {
        return [
            'assignee_order' => null,
            'filters' => [],
            'layout' => 'relaxed',
            'text_size' => 'md',
        ];
    }
}
