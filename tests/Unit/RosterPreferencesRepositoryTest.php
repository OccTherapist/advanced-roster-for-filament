<?php

namespace OccTherapist\AdvancedRosterForFilament\Tests\Unit;

use OccTherapist\AdvancedRosterForFilament\Support\RosterPreferencesRepository;
use OccTherapist\AdvancedRosterForFilament\Tests\TestCase;
use OccTherapist\AdvancedRosterForFilament\Tests\TestScope;
use OccTherapist\AdvancedRosterForFilament\Tests\TestUser;

class RosterPreferencesRepositoryTest extends TestCase
{
    public function test_it_persists_preferences_per_user_and_scope(): void
    {
        $user = TestUser::query()->create([
            'name' => 'Planner',
            'email' => 'planner@example.com',
            'password' => bcrypt('secret'),
        ]);

        $this->actingAs($user);

        $scope = new TestScope;
        $repository = app(RosterPreferencesRepository::class);

        $repository->save(['layout' => 'compact', 'assignee_order' => [3, 1, 2]], $scope);

        $this->assertSame('compact', $repository->get($scope)['layout']);
        $this->assertSame([3, 1, 2], $repository->get($scope)['assignee_order']);

        $this->assertDatabaseHas('roster_user_preferences', [
            'user_id' => $user->id,
            'scope_type' => 'test',
            'scope_id' => 1,
        ]);
    }

    public function test_it_resets_assignee_order(): void
    {
        $user = TestUser::query()->create([
            'name' => 'Planner',
            'email' => 'planner2@example.com',
            'password' => bcrypt('secret'),
        ]);

        $this->actingAs($user);

        $scope = new TestScope;
        $repository = app(RosterPreferencesRepository::class);
        $repository->save(['assignee_order' => [2, 1]], $scope);

        $repository->resetAssigneeOrder($scope);

        $this->assertNull($repository->get($scope)['assignee_order']);
    }
}
