<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Database\Builder;

use App\Enums\AssignmentRole;
use App\Enums\ContactRole;
use App\Enums\ContactType;
use App\Enums\PetitionCriteria;
use App\Enums\PetitionEventType;
use App\Enums\StatusGroup;
use App\Enums\TermType;
use App\Models\Builder\Petition\PetitionQueryBuilder;
use App\Models\Contact;
use App\Models\CustomPetitionProperty;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionAssignment;
use App\Models\PetitionEvent;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\PolicyDepartment;
use App\Models\Team;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Illuminate\Http\Request;
use Tests\Feature\FeatureTestCase;

use function count;

class PetitionQueryBuilderFilterTest extends FeatureTestCase
{
    public function testEmpty(): void
    {
        $petitionQueryBuilder = PetitionQueryBuilder::make();
        $this->assertEquals(0, $petitionQueryBuilder->count());
    }

    public function testWithoutFilters(): void
    {
        $count = $this->faker->numberBetween(3, 5);
        Petition::factory()
            ->count($count)
            ->create();

        $this->assertEquals($count, PetitionQueryBuilder::make()->count());
    }

    public function testApplicantFilterFilter(): void
    {
        $filterPetition = Petition::factory()->create();
        $applicant = Contact::factory()->state(['type' => ContactType::COMPANY])->create();
        $filterPetition->contacts()->attach($applicant, ['role' => ContactRole::APPLICANT->value]);

        $this->assertSingleFilterResult(
            PetitionCriteria::APPLICANT,
            $filterPetition->applicant->first()->type->value,
            ['applicant_id' => Contact::factory()->state(['type' => ContactType::CIVILIAN])],
        );
    }

    public function testAssignedUserFilter(): void
    {
        $user = User::factory()->create();
        $filterPetition = Petition::factory()->create();
        PetitionAssignment::factory()->create([
            'petition_id' => $filterPetition->id,
            'user_id' => $user->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        $this->assertSingleFilterResult(PetitionCriteria::ASSIGNED_USER, $user->id->toString());
    }

    public function testAssignedUserFilterNone(): void
    {
        $user = User::factory()->create();
        $petitionWithAssignee = Petition::factory()->create();
        PetitionAssignment::factory()->create([
            'petition_id' => $petitionWithAssignee->id,
            'user_id' => $user->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        $petitionWithoutAssignee = Petition::factory()->create();

        $request = new Request(['filter' => [PetitionCriteria::ASSIGNED_USER->value => 'none']]);
        $results = PetitionQueryBuilder::make($request)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($petitionWithoutAssignee->id->toString(), $results->first()->id->toString());
    }

    public function testCategoryFilter(): void
    {
        $filterPetition = Petition::factory()->create();

        $this->assertSingleFilterResult(PetitionCriteria::CATEGORY, $filterPetition->petition_category_id->toString());
    }

    public function testPetitionTypeFilter(): void
    {
        $filterPetition = Petition::factory()->create();

        $this->assertSingleFilterResult(PetitionCriteria::PETITION_TYPE, $filterPetition->petitionType->id->toString());
    }

    public function testPolicyDepartmentFilter(): void
    {
        $policyDepartment = PolicyDepartment::factory()->create();
        Petition::factory()->hasAttached($policyDepartment)->create();

        $this->assertSingleFilterResult(PetitionCriteria::POLICY_DEPARTMENT, $policyDepartment->id->toString());
    }

    public function testPolicyDepartmentFilterMultiplePolicyDepartmentsAttached(): void
    {
        $policyDepartment1 = PolicyDepartment::factory()->create();
        $policyDepartment2 = PolicyDepartment::factory()->create();
        PolicyDepartment::factory()->create();

        Petition::factory()
            ->hasAttached($policyDepartment1)
            ->hasAttached($policyDepartment2)
            ->create();

        $this->assertSingleFilterResult(PetitionCriteria::POLICY_DEPARTMENT, $policyDepartment1->id->toString());
        $this->assertSingleFilterResult(PetitionCriteria::POLICY_DEPARTMENT, $policyDepartment2->id->toString());
        $this->assertSingleFilterResult(PetitionCriteria::POLICY_DEPARTMENT, $policyDepartment2->id->toString());
    }

    public function testPolicyDepartmentFilterBuNotAttached(): void
    {
        $policyDepartment = PolicyDepartment::factory()->create();
        Petition::factory()->create();

        $request = new Request([
            'filter' => [
                PetitionCriteria::POLICY_DEPARTMENT->value => $policyDepartment->id->toString(),
            ],
        ]);

        $this->assertEquals(0, PetitionQueryBuilder::make($request)->count());
    }

    public function testStatusFilter(): void
    {
        $filterPetition = Petition::factory()->create();

        $this->assertSingleFilterResult(PetitionCriteria::STATUS, $filterPetition->petitionStatus->status);
    }

    public function testParticularitiesFilter(): void
    {
        $department = Department::factory()->create();

        $filterPetition = Petition::factory()->recycle($department)->create();
        $this->attachParticularity($filterPetition, $department, 'Alpha');

        $otherPetition = Petition::factory()->recycle($department)->create();
        $this->attachParticularity($otherPetition, $department, 'Beta');

        $request = new Request([
            'filter' => [
                PetitionCriteria::PARTICULARITIES->value => 'Alpha',
            ],
        ]);

        $results = PetitionQueryBuilder::make($request)
            ->whereDepartment($department)
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals($filterPetition->id->toString(), $results->first()->id->toString());
    }

    public function testParticularitiesFilterIgssMatchesNoticeOfDefault(): void
    {
        $department = Department::factory()->create();

        $filterPetition = Petition::factory()->recycle($department)->create();
        $filterPetition->petitionTerms()->create([
            'id' => $this->faker->uuid(),
            'type' => TermType::NOTICE_OF_DEFAULT,
            'start_date' => CalendarDate::today(),
            'duration_in_days' => $this->faker->numberBetween(1, 100),
            'penalty_amount_in_euros' => $this->faker->numberBetween(1, 100),
        ]);

        Petition::factory()->recycle($department)->create();

        $request = new Request([
            'filter' => [
                PetitionCriteria::PARTICULARITIES->value => 'IGS',
            ],
        ]);

        $results = PetitionQueryBuilder::make($request)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($filterPetition->id->toString(), $results->first()->id->toString());
    }

    public function testParticularitiesFilterIgssMatchesNoticeOfDefaultEvents(): void
    {
        $department = Department::factory()->create();

        $filterPetition = Petition::factory()->recycle($department)->create();
        PetitionEvent::factory()
            ->recycle($filterPetition)
            ->withType(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED)
            ->create();

        Petition::factory()->recycle($department)->create();

        $request = new Request([
            'filter' => [
                PetitionCriteria::PARTICULARITIES->value => 'IGS',
            ],
        ]);

        $results = PetitionQueryBuilder::make($request)
            ->whereDepartment($department)
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals($filterPetition->id->toString(), $results->first()->id->toString());
    }

    public function testSearchNumberFilter(): void
    {
        $filterPetition = Petition::factory()->create();

        $this->assertSingleFilterResult(PetitionCriteria::SEARCH, $filterPetition->number);
    }

    public function testSearchApplicantLastNameFilter(): void
    {
        $filterPetition = Petition::factory()->create();
        $applicant = Contact::factory()->state(['last_name' => $this->faker->name()])->create();
        $filterPetition->contacts()->attach($applicant, ['role' => ContactRole::APPLICANT->value]);

        $this->assertSingleFilterResult(PetitionCriteria::SEARCH, $filterPetition->applicant->first()->last_name);
    }

    public function testSearchApplicantEmailAddressFilter(): void
    {
        $filterPetition = Petition::factory()->create();
        $applicant = Contact::factory()->state(['email_address' => $this->faker->uuid()])->create();
        $filterPetition->contacts()->attach($applicant, ['role' => ContactRole::APPLICANT->value]);

        $this->assertSingleFilterResult(PetitionCriteria::SEARCH, $filterPetition->applicant->first()->email_address);
    }

    public function testSearchApplicantOrganisationNameFilter(): void
    {
        $filterPetition = Petition::factory()->create();
        $applicant = Contact::factory()->state(['organisation_name' => $this->faker->uuid()])->create();
        $filterPetition->contacts()->attach($applicant, ['role' => ContactRole::APPLICANT->value]);

        $this->assertSingleFilterResult(PetitionCriteria::SEARCH, $filterPetition->applicant->first()->organisation_name);
    }

    public function testSearchRepresentativeLastNameFilter(): void
    {
        $filterPetition = Petition::factory()->create();

        $representative = Contact::factory()->state(['last_name' => $this->faker->name()])->create();
        $filterPetition->contacts()->attach($representative, ['role' => ContactRole::REPRESENTATIVE->value]);

        $this->assertSingleFilterResult(PetitionCriteria::SEARCH, $filterPetition->representative->first()->last_name);
    }

    public function testSearchRepresentativeEmailAddressFilter(): void
    {
        $filterPetition = Petition::factory()->create();

        $representative = Contact::factory()->state(['email_address' => $this->faker->uuid()])->create();
        $filterPetition->contacts()->attach($representative, ['role' => ContactRole::REPRESENTATIVE->value]);

        $this->assertSingleFilterResult(PetitionCriteria::SEARCH, $filterPetition->representative->first()->email_address);
    }

    public function testSearchRepresentativeOrganisationNameFilter(): void
    {
        $filterPetition = Petition::factory()->create();
        $representative = Contact::factory()->state(['organisation_name' => $this->faker->uuid()])->create();
        $filterPetition->contacts()->attach($representative, ['role' => ContactRole::REPRESENTATIVE->value]);

        $this->assertSingleFilterResult(PetitionCriteria::SEARCH, $filterPetition->representative->first()->organisation_name);
    }

    public function testSearchWithCommaDoesNotCrash(): void
    {
        $filterPetition = Petition::factory()->create();
        $applicant = Contact::factory()->state(['last_name' => 'Smith', 'organisation_name' => 'John'])->create();
        $filterPetition->contacts()->attach($applicant, ['role' => ContactRole::APPLICANT->value]);

        $this->assertSingleFilterResult(PetitionCriteria::SEARCH, 'Smith, John');
    }

    public function testStatusGroupFilter(): void
    {
        $filterPetition = Petition::factory()->create([
            'petition_status_id' => PetitionStatus::factory()->state(['status_group' => StatusGroup::FINISHED]),
        ]);

        $this->assertSingleFilterResult(
            PetitionCriteria::STATUS_GROUP,
            $filterPetition->petitionStatus->status_group->value,
            ['petition_status_id' => PetitionStatus::factory()->state(['status_group' => StatusGroup::CLOSED])],
        );
    }

    public function testNotClosedStatusGroupFilter(): void
    {
        $nonClosedGroups = [StatusGroup::INTAKE, StatusGroup::PENDING, StatusGroup::FINISHED];

        foreach ($nonClosedGroups as $statusGroup) {
            Petition::factory()->create([
                'petition_status_id' => PetitionStatus::factory()->state(['status_group' => $statusGroup]),
            ]);
        }

        Petition::factory()->create([
            'petition_status_id' => PetitionStatus::factory()->state(['status_group' => StatusGroup::CLOSED]),
        ]);

        $request = new Request([
            'filter' => [
                PetitionCriteria::STATUS_GROUP->value => StatusGroup::NOT_CLOSED->value,
            ],
        ]);

        $this->assertEquals(count($nonClosedGroups), PetitionQueryBuilder::make($request)->count());
    }

    public function testCustomPropertyFilter(): void
    {
        $property = CustomPetitionProperty::factory()->create(['name' => 'Chatbesluit']);
        $filterPetition = Petition::factory()->create();
        $filterPetition->customPetitionProperties()->attach($property);

        $this->assertSingleFilterResult(PetitionCriteria::CUSTOM_PROPERTY, $property->id->toString());
    }

    public function testCustomPropertyFilterDoesNotMatchOtherPetitions(): void
    {
        $property = CustomPetitionProperty::factory()->create(['name' => 'Chatbesluit']);
        $otherProperty = CustomPetitionProperty::factory()->create(['name' => 'Andere eigenschap']);
        $filterPetition = Petition::factory()->create();
        $filterPetition->customPetitionProperties()->attach($property);
        Petition::factory()->count(2)->create()
            ->each(fn ($p) => $p->customPetitionProperties()->attach($otherProperty));

        $request = new Request([
            'filter' => [PetitionCriteria::CUSTOM_PROPERTY->value => $property->id->toString()],
        ]);
        $this->assertEquals(1, PetitionQueryBuilder::make($request)->count());
    }

    #[Test]
    public function testTeamFilter(): void
    {
        $team = Team::factory()->create();
        $filterPetition = Petition::factory()->for($team, 'team')->create();

        $this->assertSingleFilterResult(PetitionCriteria::TEAM, $filterPetition->team->id->toString());
    }

    #[Test]
    public function testTeamFilterDoesNotMatchOtherPetitions(): void
    {
        $team = Team::factory()->create();
        Petition::factory()->for($team, 'team')->create();
        Petition::factory()->count(2)->create();

        $request = new Request([
            'filter' => [
                PetitionCriteria::TEAM->value => $team->id->toString(),
            ],
        ]);

        $this->assertEquals(1, PetitionQueryBuilder::make($request)->count());
    }

    /**
     * @param array<string, mixed> $petitionAttributes
     */
    private function assertSingleFilterResult(PetitionCriteria $petitionCriteria, string $value, array $petitionAttributes = []): void
    {
        Petition::factory()
            ->count($this->faker->numberBetween(1, 3))
            ->create($petitionAttributes);

        $request = new Request([
            'filter' => [
                $petitionCriteria->value => $value,
            ],
        ]);

        $this->assertEquals(1, PetitionQueryBuilder::make($request)->count());
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
