<?php

namespace OccTherapist\AdvancedRosterForFilament\Tests\Unit;

use Illuminate\Support\Carbon;
use OccTherapist\AdvancedRosterForFilament\Data\RosterEntryData;
use OccTherapist\AdvancedRosterForFilament\Enums\RosterEntryType;
use OccTherapist\AdvancedRosterForFilament\Models\RosterEntry;
use OccTherapist\AdvancedRosterForFilament\Support\Validators\OverlapRosterEntryValidator;
use OccTherapist\AdvancedRosterForFilament\Tests\TestAssignee;
use OccTherapist\AdvancedRosterForFilament\Tests\TestCase;
use OccTherapist\AdvancedRosterForFilament\Tests\TestScope;

class OverlapRosterEntryValidatorTest extends TestCase
{
    public function test_it_detects_overlapping_entries(): void
    {
        $scope = new TestScope;
        $assignee = TestAssignee::query()->create(['name' => 'Alex']);

        RosterEntry::query()->create([
            'scope_type' => $scope->getRosterScopeType(),
            'scope_id' => $scope->getRosterScopeKey(),
            'section_key' => 'assignees',
            'assignee_type' => TestAssignee::class,
            'assignee_id' => $assignee->id,
            'entry_type' => RosterEntryType::WORK->value,
            'start_at' => '2026-06-22 09:00:00',
            'end_at' => '2026-06-22 10:00:00',
        ]);

        $entryData = new RosterEntryData(
            scope: $scope,
            sectionKey: 'assignees',
            assigneeType: TestAssignee::class,
            assigneeId: $assignee->id,
            entryType: RosterEntryType::WORK,
            startAt: Carbon::parse('2026-06-22 09:30:00'),
            endAt: Carbon::parse('2026-06-22 11:00:00'),
        );

        $result = (new OverlapRosterEntryValidator)->validate($entryData);

        $this->assertFalse($result->isValid());
    }

    public function test_it_allows_non_overlapping_entries(): void
    {
        $scope = new TestScope;
        $assignee = TestAssignee::query()->create(['name' => 'Alex']);

        RosterEntry::query()->create([
            'scope_type' => $scope->getRosterScopeType(),
            'scope_id' => $scope->getRosterScopeKey(),
            'section_key' => 'assignees',
            'assignee_type' => TestAssignee::class,
            'assignee_id' => $assignee->id,
            'entry_type' => RosterEntryType::WORK->value,
            'start_at' => '2026-06-22 09:00:00',
            'end_at' => '2026-06-22 10:00:00',
        ]);

        $entryData = new RosterEntryData(
            scope: $scope,
            sectionKey: 'assignees',
            assigneeType: TestAssignee::class,
            assigneeId: $assignee->id,
            entryType: RosterEntryType::WORK,
            startAt: Carbon::parse('2026-06-22 10:00:00'),
            endAt: Carbon::parse('2026-06-22 11:00:00'),
        );

        $result = (new OverlapRosterEntryValidator)->validate($entryData);

        $this->assertTrue($result->isValid());
    }
}
