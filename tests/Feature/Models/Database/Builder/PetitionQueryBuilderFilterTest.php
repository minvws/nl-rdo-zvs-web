<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Database\Builder;

use App\Enums\ContactRole;
use App\Enums\ContactType;
use App\Enums\PetitionCriteria;
use App\Enums\StatusGroup;
use App\Models\Builder\Petition\PetitionQueryBuilder;
use App\Models\Contact;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PolicyDepartment;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\Feature\FeatureTestCase;

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
        $filterPetition = Petition::factory()->create(['assigned_to' => User::factory()]);

        $this->assertSingleFilterResult(PetitionCriteria::ASSIGNED_USER, $filterPetition->assigned_to->toString());
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
}
