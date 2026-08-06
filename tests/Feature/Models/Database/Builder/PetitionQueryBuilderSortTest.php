<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Database\Builder;

use App\Enums\AssignmentRole;
use App\Enums\ContactType;
use App\Enums\PetitionCriteria;
use App\Models\Builder\Petition\PetitionQueryBuilder;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Illuminate\Http\Request;
use Tests\Feature\FeatureTestCase;

use function sprintf;

class PetitionQueryBuilderSortTest extends FeatureTestCase
{
    public function testSortByNumber(): void
    {
        $petition1 = Petition::factory()->create(['number' => 1]);
        $petition2 = Petition::factory()->create(['number' => 2]);

        $this->assertOrder(PetitionCriteria::NUMBER, $petition1, $petition2);
    }

    public function testSortByName(): void
    {
        $petition1 = Petition::factory()->create(['name' => 'aaa']);
        $petition2 = Petition::factory()->create(['name' => 'bbb']);

        $this->assertOrder(PetitionCriteria::NAME, $petition1, $petition2);
    }

    public function testSortByDeadlineAt(): void
    {
        $petition1 = Petition::factory()->create([
            'deadline_at' => CalendarDate::createFromFormat('d-m-Y', '1-1-2000'),
        ]);
        $petition2 = Petition::factory()->create([
            'deadline_at' => CalendarDate::createFromFormat('d-m-Y', '1-1-2001'),
        ]);

        $this->assertOrder(PetitionCriteria::DEADLINE_AT, $petition1, $petition2);
    }

    public function testSortByApplicant(): void
    {
        $petition1 = Petition::factory()->create([
            'applicant_id' => Contact::factory()->state(['type' => ContactType::CIVILIAN]),
        ]);
        $petition2 = Petition::factory()->create([
            'applicant_id' => Contact::factory()->state(['type' => ContactType::LEGAL_SPECIALIST]),
        ]);

        $this->assertOrder(PetitionCriteria::APPLICANT, $petition1, $petition2);
    }

    public function testSortByAssignedUser(): void
    {
        $petition2 = Petition::factory()->create();
        $user2 = User::factory()->create(['name' => 'bbb']);

        $petition1 = Petition::factory()->create();
        $user1 = User::factory()->create(['name' => 'aaa']);

        $petition1->assignments()->create(['user_id' => $user1->id, 'assignment_role' => AssignmentRole::PRIMARY]);
        $petition2->assignments()->create(['user_id' => $user2->id, 'assignment_role' => AssignmentRole::PRIMARY]);

        $this->assertOrder(PetitionCriteria::ASSIGNED_USER, $petition1, $petition2);
    }

    public function testSortByCategory(): void
    {
        $petition1 = Petition::factory()->create([
            'petition_category_id' => PetitionCategory::factory()->state(['name' => 'aaa']),
        ]);
        $petition2 = Petition::factory()->create([
            'petition_category_id' => PetitionCategory::factory()->state(['name' => 'bbb']),
        ]);

        $this->assertOrder(PetitionCriteria::CATEGORY, $petition1, $petition2);
    }

    public function testSortByPetitionType(): void
    {
        $petition1 = Petition::factory()->create([
            'petition_type_id' => PetitionType::factory()->state(['name' => 'aaa']),
        ]);
        $petition2 = Petition::factory()->create([
            'petition_type_id' => PetitionType::factory()->state(['name' => 'bbb']),
        ]);

        $this->assertOrder(PetitionCriteria::PETITION_TYPE, $petition1, $petition2);
    }

    public function testSortByStatus(): void
    {
        $petition1 = Petition::factory()->create([
            'petition_status_id' => PetitionStatus::factory()->state(['order' => 1]),
        ]);
        $petition2 = Petition::factory()->create([
            'petition_status_id' => PetitionStatus::factory()->state(['order' => 2]),
        ]);

        $this->assertOrder(PetitionCriteria::STATUS, $petition1, $petition2);
    }

    public function testSortByStatusGroup(): void
    {
        $petition1 = Petition::factory()->create([
            'petition_status_id' => PetitionStatus::factory()->state([
                'order' => 1,
            ]),
        ]);
        $petition2 = Petition::factory()->create([
            'petition_status_id' => PetitionStatus::factory()->state([
                'order' => 2,
            ]),
        ]);

        $this->assertOrder(PetitionCriteria::STATUS_GROUP, $petition1, $petition2);
    }

    public function testSortBySumOfPenaltiesPerDate(): void
    {
        $petition1 = Petition::factory()->create([
            'legacy_term_penalty_today' => 100,
            'igs_penalty_today' => 0,
            'bnt_penalty_today' => 0,
        ]);

        $petition2 = Petition::factory()->create([
            'legacy_term_penalty_today' => 200,
            'igs_penalty_today' => 0,
            'bnt_penalty_today' => 0,
        ]);

        $this->assertOrder(PetitionCriteria::SUM_OF_PENALTIES_PER_DATE, $petition1, $petition2);
    }

    public function testSortByPenaltyToDate(): void
    {
        $petition1 = Petition::factory()->create([
            'legacy_term_forfeited' => 100,
            'igs_forfeited' => 0,
            'bnt_forfeited' => 0,
        ]);

        $petition2 = Petition::factory()->create([
            'legacy_term_forfeited' => 200,
            'igs_forfeited' => 0,
            'bnt_forfeited' => 0,
        ]);

        $this->assertOrder(PetitionCriteria::PENALTY_TO_DATE, $petition1, $petition2);
    }

    public function testSortByParticularities(): void
    {
        $department = Department::factory()->create();

        $petition1 = Petition::factory()->recycle($department)->create();
        $this->attachParticularity($petition1, $department, 'Alpha');
        $this->attachParticularity($petition1, $department, 'Beta');

        $petition2 = Petition::factory()->recycle($department)->create();
        $this->attachParticularity($petition2, $department, 'Alpha');
        $this->attachParticularity($petition2, $department, 'Gamma');

        $petition3 = Petition::factory()->recycle($department)->create();

        $this->assertOrder(PetitionCriteria::PARTICULARITIES, $petition1, $petition2, $petition3, $department);
    }

    private function assertOrder(
        PetitionCriteria $petitionCriteria,
        Petition $petition1,
        Petition $petition2,
        ?Petition $petition3 = null,
        ?Department $department = null,
    ): void {
        // assert asc
        $request = new Request(['sort' => $petitionCriteria->value]);
        $petitionQueryBuilder = PetitionQueryBuilder::make($request);
        if ($department instanceof Department) {
            $petitionQueryBuilder->whereDepartment($department);
        }
        if ($petitionCriteria === PetitionCriteria::SUM_OF_PENALTIES_PER_DATE) {
            $petitionQueryBuilder->withSumOfPenaltiesPerDate();
        }
        if ($petitionCriteria === PetitionCriteria::PENALTY_TO_DATE) {
            $petitionQueryBuilder->withPenaltyToDate();
        }
        $petitionQueryBuilderCollection = $petitionQueryBuilder->get();

        $this->assertEquals($petition1->id->toString(), $petitionQueryBuilderCollection->first()->id->toString());
        if ($petition3 instanceof Petition) {
            $this->assertEquals($petition3->id->toString(), $petitionQueryBuilderCollection->last()->id->toString());
        } else {
            $this->assertEquals($petition2->id->toString(), $petitionQueryBuilderCollection->last()->id->toString());
        }

        // assert desc
        $request = new Request(['sort' => sprintf('-%s', $petitionCriteria->value)]);
        $petitionQueryBuilder = PetitionQueryBuilder::make($request);
        if ($department instanceof Department) {
            $petitionQueryBuilder->whereDepartment($department);
        }
        if ($petitionCriteria === PetitionCriteria::SUM_OF_PENALTIES_PER_DATE) {
            $petitionQueryBuilder->withSumOfPenaltiesPerDate();
        }
        if ($petitionCriteria === PetitionCriteria::PENALTY_TO_DATE) {
            $petitionQueryBuilder->withPenaltyToDate();
        }
        $petitionQueryBuilderCollection = $petitionQueryBuilder->get();

        if ($petition3 instanceof Petition) {
            $this->assertEquals($petition2->id->toString(), $petitionQueryBuilderCollection->first()->id->toString());
            $this->assertEquals($petition3->id->toString(), $petitionQueryBuilderCollection->last()->id->toString());
        } else {
            $this->assertEquals($petition2->id->toString(), $petitionQueryBuilderCollection->first()->id->toString());
            $this->assertEquals($petition1->id->toString(), $petitionQueryBuilderCollection->last()->id->toString());
        }
    }

    private function attachParticularity(Petition $petition, Department $department, string $label): void
    {
        $relatedDepartment = Department::factory()->create();
        $relatedPetitionType = PetitionType::factory()->recycle($relatedDepartment)->create([
            'particularity_label' => $label,
        ]);

        $relatedPetition = Petition::factory()->recycle($relatedDepartment)->recycle($relatedPetitionType)->create();

        $petition->relatedPetitions()->attach($relatedPetition);
    }
}
