<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\CustomDateLabel;
use App\Models\Decision;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionStatusHistory;
use App\Models\PolicyDepartment;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function now;

class PetitionTest extends FeatureTestCase
{
    #[Test]
    public function testPetitionStatusHistoriesRelationship(): void
    {
        $petition = Petition::factory()->create();

        $history = $petition->petitionStatusHistories()->create([
            'petition_status_id' => $petition->petition_status_id,
            'date' => now()->format('Y-m-d'),
        ]);

        $this->assertInstanceOf(PetitionStatusHistory::class, $history);
        $this->assertEquals($petition->id, $history->petition_id);
        $this->assertEquals(1, $petition->petitionStatusHistories->count());
    }

    #[Test]
    public function testAsssignedUser(): void
    {
        $user = User::factory()->create();
        $petition = Petition::factory()
            ->create([
                'assigned_to' => $user->id,
            ]);

        $this->assertEquals($user->id, $petition->assignedUser->id);
    }

    #[Test]
    public function testPolicyDepartments(): void
    {
        $policyDepartment = PolicyDepartment::factory()->create();
        $petition = Petition::factory()
            ->hasAttached($policyDepartment)
            ->create();

        $this->assertEquals($policyDepartment->id, $petition->policyDepartments->first()->id);
    }

    #[Test]
    public function testRelatedPetitions(): void
    {
        $petition = Petition::factory()->create();
        $childPetition = Petition::factory()->create();
        $parentPetition = Petition::factory()->create();

        $petition->relatedPetitions()->attach($childPetition);
        $petition->relatedPetitions()->attach($parentPetition);

        $this->assertCount(2, $petition->relatedPetitions);

        $this->assertTrue($petition->relatedPetitions->contains($childPetition->id));
        $this->assertTrue($petition->relatedPetitions->contains($parentPetition->id));
    }

    public function testDaysPendingWhenDateOfCloseIsNull(): void
    {
        $petition = Petition::factory()
            ->create([
                'date_of_entry' => CalendarDate::today()->subDays(10)->format('Y-m-d'),
            ]);
        $this->assertEquals(10, $petition->daysPending);
    }

    public function testDaysPendingWhenDateOfCloseIsFilled(): void
    {
        $petition = Petition::factory()
            ->create([
                'date_of_entry' => CalendarDate::today()->subDays(15)->format('Y-m-d'),
            ]);

        $customDates = [
            'date' => $petition->date_of_entry->addDays(10)->format('Y-m-d'),
            'date_label' => CustomDateLabel::DATE_RULING->value,
        ];
        $petition->customDates()->create($customDates);
        $this->assertEquals(10, $petition->daysPending);
    }

    #[Test]
    public function testCreateModelAndSyncDecisionRelation(): void
    {
        $decision = Decision::factory()->create();
        $petition = Petition::factory()->create();

        $petition->decisions()->sync([$decision->id->toString()]);

        $this->assertDatabaseHas('decision_petition', [
            'decision_id' => $decision->id,
            'petition_id' => $petition->id,
        ]);
    }

    #[Test]
    public function testFindDecisionsForPetition(): void
    {
        $department = Department::factory()->create();
        $decisions = Decision::factory()->count(2)->recycle($department)->create();
        $unrelatedDecisions = Decision::factory()->count(2)->recycle($department)->create();
        $petition = Petition::factory()->recycle($department)->create();

        $petition->decisions()->sync(
            $decisions->map->id->map->toString()->toArray(),
        );

        $retrievedDecisions = $petition->decisions;

        $this->assertCount(2, $retrievedDecisions);
        $this->assertNotContains($unrelatedDecisions, $retrievedDecisions);
        $this->assertInstanceOf(Collection::class, $retrievedDecisions);
    }
}
