<?php

declare(strict_types=1);

namespace Tests\Feature\Commands\Petition;

use App\Enums\Occurrence;
use App\Enums\PetitionVariant;
use App\Enums\TimelineType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\TimelineItem;
use Tests\Feature\FeatureTestCase;

class SuffixWooObjectionsCommandTest extends FeatureTestCase
{
    private Department $department;
    private PetitionType $bezwaarType;
    private PetitionCategory $wooCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create(['slug' => 'wjz-bb', 'name' => 'WJZ']);
        $this->bezwaarType = PetitionType::factory()
            ->recycle($this->department)
            ->create(['type' => PetitionVariant::BEZWAAR]);
        $this->wooCategory = PetitionCategory::factory()
            ->recycle($this->department)
            ->create(['name' => 'Woo-verzoek']);
    }

    public function testDepartmentNotFound(): void
    {
        $this->artisan('petitions:suffix-woo-objections', ['--department' => 'nope'])
            ->expectsOutput('Department "nope" not found')
            ->assertFailed();
    }

    public function testNoWooCategories(): void
    {
        $other = Department::factory()->create(['slug' => 'team-a', 'name' => 'Team A']);
        PetitionCategory::factory()->recycle($other)->create(['name' => 'Woonwagenwet']);
        PetitionCategory::factory()->recycle($other)->create(['name' => 'Awb']);

        $this->artisan('petitions:suffix-woo-objections', ['--department' => 'team-a'])
            ->expectsOutput('No Woo/Wob categories found for department "Team A"')
            ->assertFailed();
    }

    public function testDryRunListsButDoesNotRename(): void
    {
        $petition = $this->wooObjection('2023001275');

        $this->artisan('petitions:suffix-woo-objections')
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Matching categories: Woo-verzoek')
            ->expectsOutput('Found 1 Woo/Wob objection(s) in department "WJZ"')
            ->expectsOutputToContain('2023001275 -> 2023001275-W (Woo-verzoek, ')
            ->expectsOutput('Would rename 1 petition(s), 0 already had the suffix, 0 skipped because the new number exists')
            ->assertSuccessful();

        $this->assertSame('2023001275', $petition->refresh()->number);
    }

    public function testCommitRenamesOnlyWooAndWobObjectionsOfTheDepartment(): void
    {
        $wobCategory = PetitionCategory::factory()->recycle($this->department)->create(['name' => 'Wob']);
        $otherCategory = PetitionCategory::factory()->recycle($this->department)->create(['name' => 'Woonwagenwet']);
        $beroepType = PetitionType::factory()->recycle($this->department)->create(['type' => PetitionVariant::BEROEP]);
        $otherDepartment = Department::factory()->create(['slug' => 'team-c']);
        $otherDepartmentType = PetitionType::factory()->recycle($otherDepartment)->create(['type' => PetitionVariant::BEZWAAR]);
        $otherDepartmentCategory = PetitionCategory::factory()->recycle($otherDepartment)->create(['name' => 'Woo']);

        $woo = $this->wooObjection('2023000001');
        $wob = $this->wooObjection('2023000002', category: $wobCategory);
        $otherCategoryObjection = $this->wooObjection('2023000003', category: $otherCategory);
        $wooAppeal = $this->wooObjection('2023000004', type: $beroepType);
        $otherDepartmentObjection = Petition::factory()->create([
            'number' => '2023000005',
            'department_id' => $otherDepartment->id,
            'petition_type_id' => $otherDepartmentType->id,
            'petition_category_id' => $otherDepartmentCategory->id,
        ]);

        $this->artisan('petitions:suffix-woo-objections', ['--commit' => true])
            ->doesntExpectOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Matching categories: Wob, Woo-verzoek')
            ->expectsOutput('Found 2 Woo/Wob objection(s) in department "WJZ"')
            ->expectsOutput('Renamed 2 petition(s), 0 already had the suffix, 0 skipped because the new number exists')
            ->assertSuccessful();

        $this->assertSame('2023000001-W', $woo->refresh()->number);
        $this->assertSame('2023000002-W', $wob->refresh()->number);
        $this->assertSame('2023000003', $otherCategoryObjection->refresh()->number);
        $this->assertSame('2023000004', $wooAppeal->refresh()->number);
        $this->assertSame('2023000005', $otherDepartmentObjection->refresh()->number);
    }

    public function testUpdatesTimelineReferencesOnRelatedPetitions(): void
    {
        $woo = $this->wooObjection('2023000010');
        $related = Petition::factory()->recycle($this->department)->create();
        $otherPetition = Petition::factory()->recycle($this->department)->create();

        $woo->relatedPetitions()->attach($related);

        $timelineItem = TimelineItem::factory()
            ->recycle($related)
            ->create([
                'type' => TimelineType::REFERENCED_OCCURRENCE,
                'data' => [
                    'type' => Occurrence::PETITION_TYPE->value,
                    'action' => Occurrence::ATTACH_ACTION->value,
                    'subject' => $woo->number,
                ],
            ]);

        // Timeline item on an unrelated petition with a coincidentally-matching subject must be left alone.
        $unrelated = Petition::factory()->recycle($this->department)->create();
        $unrelatedTimelineItem = TimelineItem::factory()
            ->recycle($unrelated)
            ->create([
                'type' => TimelineType::REFERENCED_OCCURRENCE,
                'data' => [
                    'type' => Occurrence::PETITION_TYPE->value,
                    'action' => Occurrence::ATTACH_ACTION->value,
                    'subject' => $woo->number,
                ],
            ]);

        // Non-REFERENCED_OCCURRENCE item on the related petition must be left alone
        $statusItem = TimelineItem::factory()
            ->recycle($related)
            ->create([
                'type' => TimelineType::STATUS_OCCURRENCE,
                'data' => ['subject' => $woo->number],
            ]);

        // REFERENCED_OCCURRENCE item about a decision (not a petition) must be left alone
        $decisionReferenceItem = TimelineItem::factory()
            ->recycle($related)
            ->create([
                'type' => TimelineType::REFERENCED_OCCURRENCE,
                'data' => [
                    'type' => Occurrence::DECISION_TYPE->value,
                    'action' => Occurrence::ATTACH_ACTION->value,
                    'subject' => $woo->number,
                ],
            ]);

        // REFERENCED_OCCURRENCE item about a different petition must be left alone
        $otherSubjectItem = TimelineItem::factory()
            ->recycle($related)
            ->create([
                'type' => TimelineType::REFERENCED_OCCURRENCE,
                'data' => [
                    'type' => Occurrence::PETITION_TYPE->value,
                    'action' => Occurrence::ATTACH_ACTION->value,
                    'subject' => $otherPetition->number,
                ],
            ]);

        $this->artisan('petitions:suffix-woo-objections', ['--commit' => true])
            ->expectsOutputToContain('Timeline: reference to 2023000010 on petition')
            ->expectsOutput('Updated 1 timeline reference(s) on related petitions')
            ->assertSuccessful();

        $this->assertSame('2023000010-W', $woo->refresh()->number);
        $this->assertSame('2023000010-W', $timelineItem->refresh()->data['subject']);
        $this->assertSame('2023000010', $unrelatedTimelineItem->refresh()->data['subject']);
        $this->assertSame('2023000010', $statusItem->refresh()->data['subject']);
        $this->assertSame('2023000010', $decisionReferenceItem->refresh()->data['subject']);
        $this->assertSame($otherPetition->number, $otherSubjectItem->refresh()->data['subject']);
    }

    public function testSkipsAlreadySuffixedAndConflictingNumbers(): void
    {
        $suffixed = $this->wooObjection('2023000001-W');
        $conflicting = $this->wooObjection('2023000002');
        Petition::factory()->create(['number' => '2023000002-W']);
        $plain = $this->wooObjection('2023000003');

        $this->artisan('petitions:suffix-woo-objections', ['--commit' => true])
            ->expectsOutput('  Skipping 2023000002: 2023000002-W already exists')
            ->expectsOutput('Renamed 1 petition(s), 1 already had the suffix, 1 skipped because the new number exists')
            ->assertSuccessful();

        $this->assertSame('2023000001-W', $suffixed->refresh()->number);
        $this->assertSame('2023000002', $conflicting->refresh()->number);
        $this->assertSame('2023000003-W', $plain->refresh()->number);
    }

    private function wooObjection(string $number, ?PetitionCategory $category = null, ?PetitionType $type = null): Petition
    {
        $type ??= $this->bezwaarType;

        return Petition::factory()->create([
            'number' => $number,
            'department_id' => $this->department->id,
            'petition_type_id' => $type->id,
            'petition_status_id' => PetitionStatus::factory()->recycle($type)->create()->id,
            'petition_category_id' => ($category ?? $this->wooCategory)->id,
        ]);
    }
}
