<?php

namespace OccTherapist\AdvancedRosterForFilament\Tests\Unit;

use Filament\Forms\Components\CheckboxList;
use Illuminate\Support\Collection;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterFilter;
use OccTherapist\AdvancedRosterForFilament\Contracts\RosterScope;
use OccTherapist\AdvancedRosterForFilament\Support\Filters\AssigneeRosterFilter;
use OccTherapist\AdvancedRosterForFilament\Support\RosterFilterRegistry;
use OccTherapist\AdvancedRosterForFilament\Support\RosterPreferencesRepository;
use OccTherapist\AdvancedRosterForFilament\Tests\TestAssignee;
use OccTherapist\AdvancedRosterForFilament\Tests\TestCase;
use OccTherapist\AdvancedRosterForFilament\Tests\TestScope;
use OccTherapist\AdvancedRosterForFilament\Tests\TestUser;

class RosterFilterRegistryTest extends TestCase
{
    public function test_it_applies_assignee_filter_when_ids_are_selected(): void
    {
        $first = TestAssignee::query()->create(['name' => 'Alice']);
        $second = TestAssignee::query()->create(['name' => 'Bob']);
        $third = TestAssignee::query()->create(['name' => 'Charlie']);

        $user = TestUser::query()->create([
            'name' => 'Planner',
            'email' => 'planner@example.com',
            'password' => bcrypt('secret'),
        ]);

        $this->actingAs($user);

        $scope = new TestScope;
        app(RosterPreferencesRepository::class)->save([
            'filters' => [
                'assignees' => [$first->id, $third->id],
            ],
        ], $scope);

        $assignees = collect([$first, $second, $third]);
        $filtered = app(RosterFilterRegistry::class)->apply($assignees, $scope);

        $this->assertCount(2, $filtered);
        $this->assertTrue($filtered->contains(fn ($assignee) => $assignee->is($first)));
        $this->assertTrue($filtered->contains(fn ($assignee) => $assignee->is($third)));
        $this->assertFalse($filtered->contains(fn ($assignee) => $assignee->is($second)));
    }

    public function test_empty_filter_values_show_all_assignees(): void
    {
        $assignees = collect([
            TestAssignee::query()->create(['name' => 'Alice']),
            TestAssignee::query()->create(['name' => 'Bob']),
        ]);

        $filtered = app(RosterFilterRegistry::class)->apply($assignees, new TestScope);

        $this->assertCount(2, $filtered);
    }

    public function test_it_counts_active_filters(): void
    {
        $user = TestUser::query()->create([
            'name' => 'Planner',
            'email' => 'planner2@example.com',
            'password' => bcrypt('secret'),
        ]);

        $this->actingAs($user);

        $scope = new TestScope;
        app(RosterPreferencesRepository::class)->save([
            'filters' => [
                'assignees' => [1],
            ],
        ], $scope);

        $this->assertSame(1, app(RosterFilterRegistry::class)->countActive($scope));
    }

    public function test_it_applies_multiple_filters_with_and_logic(): void
    {
        $registry = app(RosterFilterRegistry::class);
        $registry->register(new class implements RosterFilter
        {
            public function getKey(): string
            {
                return 'role';
            }

            public function getLabel(): string
            {
                return 'Role';
            }

            public function getFormComponent(): CheckboxList
            {
                return CheckboxList::make($this->getKey());
            }

            public function apply(Collection $assignees, mixed $value, ?RosterScope $scope): Collection
            {
                if (! is_array($value) || $value === []) {
                    return $assignees;
                }

                return $assignees->filter(fn ($assignee) => in_array($assignee->name[0], $value, true));
            }
        });

        $alice = TestAssignee::query()->create(['name' => 'Alice']);
        $bob = TestAssignee::query()->create(['name' => 'Bob']);
        $anna = TestAssignee::query()->create(['name' => 'Anna']);

        $user = TestUser::query()->create([
            'name' => 'Planner',
            'email' => 'planner3@example.com',
            'password' => bcrypt('secret'),
        ]);

        $this->actingAs($user);

        $scope = new TestScope;
        app(RosterPreferencesRepository::class)->save([
            'filters' => [
                'assignees' => [$alice->id, $bob->id, $anna->id],
                'role' => ['A'],
            ],
        ], $scope);

        $filtered = $registry->apply(collect([$alice, $bob, $anna]), $scope);

        $this->assertCount(2, $filtered);
        $this->assertTrue($filtered->contains(fn ($assignee) => $assignee->is($alice)));
        $this->assertTrue($filtered->contains(fn ($assignee) => $assignee->is($anna)));
        $this->assertFalse($filtered->contains(fn ($assignee) => $assignee->is($bob)));
    }

    public function test_assignee_filter_exposes_checkbox_list_component(): void
    {
        $filter = new AssigneeRosterFilter;

        $this->assertSame('assignees', $filter->getKey());
        $this->assertInstanceOf(CheckboxList::class, $filter->getFormComponent());
    }
}
