<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition;

use App\Actions\Petition\PetitionCreateAction;
use App\Enums\PetitionTypeType;
use App\Enums\TermType;
use App\Enums\TimelineType;
use App\Models\Department;
use App\Models\DepartmentTermTypeSetting;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use App\Models\PetitionTerm;
use App\Models\PetitionType;
use App\Models\TimelineItem;
use App\Models\User;
use App\Services\LegalTermDeadlineCalculator;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

use function json_encode;

class PetitionCreateActionTest extends FeatureTestCase
{
    public function testPetitionUpdatesDeadlineAtWithFirstTerm(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['last_visited_department_id' => $department->id]);
        $this->actingAs($user);

        $name = $this->faker->word();
        $dateOfEntry = $this->faker->calendarDate();

        $description = $this->faker->sentence();
        $firstTermDurationInDays = $this->faker->numberBetween(1, 100);
        $firstTermPenaltyAmountInEuros = $this->faker->optional()->numberBetween(1, 100);

        $petitionCategory = PetitionCategory::factory()->recycle($department)->create();
        $petitionType = PetitionType::factory()->recycle($department)->create([
            'type' => PetitionTypeType::WOO_VERZOEK,
        ]);
        $petitionStatus = PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        DepartmentTermTypeSetting::factory()->recycle($department)->create([
            'term_type' => TermType::FIRST,
            'field' => 'duration_in_days',
            'active' => true,
            'default_value' => $firstTermDurationInDays,
        ]);
        DepartmentTermTypeSetting::factory()->recycle($department)->create([
            'term_type' => TermType::FIRST,
            'field' => 'penalty_amount_in_euros',
            'active' => true,
            'default_value' => $firstTermPenaltyAmountInEuros,
        ]);

        $attributes = [
            'petition_type_id' => $petitionType->id->toString(),
            'petition_category_id' => $petitionCategory->id->toString(),
            'name' => $name,
            'date_of_entry' => $dateOfEntry->format('Y-m-d'),
            'description' => $description,
        ];

        $petitionCreateAction = $this->getPetitionTermsCreateAction();
        $petitionCreateAction->execute($department, $user, $petitionType, $attributes);

        $this->assertDatabaseHas(Petition::class, [
            'petition_status_id' => $petitionStatus->id,
            'petition_type_id' => $petitionType->id,
            'petition_category_id' => $petitionCategory->id,
            'name' => $name,
            'date_of_entry' => $dateOfEntry->format('Y-m-d'),
            'description' => $description,
        ]);
        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::FIRST,
            'start_date' => $dateOfEntry->addDay()->format('Y-m-d'),
            'duration_in_days' => $firstTermDurationInDays,
            'penalty_amount_in_euros' => $firstTermPenaltyAmountInEuros ?? 0,
        ]);
        $this->assertDatabaseHas(PetitionStatusHistory::class, [
            'petition_status_id' => $petitionStatus->id,
            'date' => $dateOfEntry->addDay()->toDateString(),
        ]);
        $this->assertDatabaseHas(TimelineItem::class, [
            'user_id' => $user->id,
            'type' => TimelineType::TIMELINEABLE_CREATED,
            'data' => null,
        ]);
        $this->assertDatabaseHas(TimelineItem::class, [
            'user_id' => $user->id,
            'type' => TimelineType::STATUS_OCCURRENCE,
            'data' => json_encode([
                'previous_status' => null,
                'current_status' => $petitionStatus->status,
                'date' => $dateOfEntry->addDay()->toDateString(),
            ]),
        ]);
    }

    public function testPetitionUpdatesDeadlineAtWithFirstTermWithoutConfiguration(): void
    {
        $department = Department::factory()->create();
        $this->actingAs(User::factory()->create(['last_visited_department_id' => $department->id]));

        $dateOfEntry = $this->faker->calendarDate();
        $defaultFirstTermDurationInDays = $this->faker->numberBetween(1, 100);

        ConfigHelper::set('petition_term.default_first_term_duration_in_days', $defaultFirstTermDurationInDays);

        $petitionCategory = PetitionCategory::factory()->recycle($department)->create();
        $petitionType = PetitionType::factory()->recycle($department)->create([
            'type' => PetitionTypeType::WOO_VERZOEK,
        ]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $attributes = [
            'petition_type_id' => $petitionType->id->toString(),
            'petition_category_id' => $petitionCategory->id->toString(),
            'name' => $this->faker->word(),
            'date_of_entry' => $dateOfEntry->format('Y-m-d'),
            'description' => $this->faker->sentence(),
        ];

        $petitionCreateAction = $this->getPetitionTermsCreateAction();
        $petitionCreateAction->execute($department, new User(), $petitionType, $attributes);

        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::FIRST,
            'start_date' => $dateOfEntry->addDay()->format('Y-m-d'),
            'duration_in_days' => $defaultFirstTermDurationInDays,
            'penalty_amount_in_euros' => $firstTermPenaltyAmountInEuros ?? 0,
        ]);
    }

    public function testPetitionCreateAsBezwaarWithFirstTermAndDateOfEntryNotAfterObjectionPeriod(): void
    {
        $department = Department::factory()->create();
        $this->actingAs(User::factory()->create(['last_visited_department_id' => $department->id]));

        $name = $this->faker->word();
        $dateAppealedDecision = $this->faker->calendarDate();
        $objectionPeriodTermDurationInDays = $this->faker->numberBetween(1, 100);
        $dateOfEntry = $dateAppealedDecision->addDays($objectionPeriodTermDurationInDays);
        $description = $this->faker->sentence();
        $firstTermDurationInDays = $this->faker->numberBetween(1, 100);

        $petitionCategory = PetitionCategory::factory()->recycle($department)->create();
        $petitionType = PetitionType::factory()->recycle($department)->create([
            'type' => PetitionTypeType::BEZWAAR,
        ]);
        $petitionStatus = PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        DepartmentTermTypeSetting::factory()->recycle($department)->create([
            'term_type' => TermType::FIRST,
            'field' => 'duration_in_days',
            'active' => true,
            'default_value' => $firstTermDurationInDays,
        ]);
        DepartmentTermTypeSetting::factory()->recycle($department)->create([
            'term_type' => TermType::OBJECTION_PERIOD,
            'field' => 'duration_in_days',
            'active' => true,
            'default_value' => $objectionPeriodTermDurationInDays,
        ]);

        $attributes = [
            'petition_type_id' => $petitionType->id->toString(),
            'petition_category_id' => $petitionCategory->id->toString(),
            'name' => $name,
            'date_of_entry' => $dateOfEntry->format('Y-m-d'),
            'date_appealed_decision' => $dateAppealedDecision->format('Y-m-d'),
            'description' => $description,
        ];

        $petitionCreateAction = $this->getPetitionTermsCreateAction();
        $petitionCreateAction->execute($department, new User(), $petitionType, $attributes);

        $objectionPeriodEndDate = $dateAppealedDecision->addDays($objectionPeriodTermDurationInDays);
        $atwAdjustedObjectionEndDate = $this->app->get(LegalTermDeadlineCalculator::class)->calculate($objectionPeriodEndDate);
        $expectedFirstTermStartDate = $atwAdjustedObjectionEndDate->addDay();

        $this->assertDatabaseHas(Petition::class, [
            'petition_status_id' => $petitionStatus->id,
            'petition_type_id' => $petitionType->id,
            'petition_category_id' => $petitionCategory->id,
            'name' => $name,
            'date_of_entry' => $dateOfEntry->format('Y-m-d'),
            'date_appealed_decision' => $dateAppealedDecision->format('Y-m-d'),
            'description' => $description,
        ]);
        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::FIRST,
            'start_date' => $expectedFirstTermStartDate->format('Y-m-d'),
            'duration_in_days' => $firstTermDurationInDays,
            'penalty_amount_in_euros' => 0,
        ]);
        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::OBJECTION_PERIOD,
            'start_date' => $dateAppealedDecision->addDay()->format('Y-m-d'),
            'duration_in_days' => $objectionPeriodTermDurationInDays,
            'penalty_amount_in_euros' => 0,
        ]);
        $this->assertDatabaseHas(TimelineItem::class, [
            'type' => TimelineType::TIMELINEABLE_CREATED,
            'data' => null,
        ]);
        $this->assertDatabaseHas(TimelineItem::class, [
            'type' => TimelineType::TERM_CREATED,
            'data' => json_encode(['term_type' => TermType::FIRST]),
        ]);
        $this->assertDatabaseHas(TimelineItem::class, [
            'type' => TimelineType::TERM_CREATED,
            'data' => json_encode(['term_type' => TermType::OBJECTION_PERIOD]),
        ]);
    }

    public function testPetitionCreateAsBezwaarWithFirstTermAndDateOfEntryAfterTermEndDate(): void
    {
        $department = Department::factory()->create();
        $this->actingAs(User::factory()->create(['last_visited_department_id' => $department->id]));

        $dateAppealedDecision = $this->faker->calendarDate();
        $name = $this->faker->word();
        $objectionPeriodTermDurationInDays = $this->faker->numberBetween(1, 100);
        $dateOfEntry = $dateAppealedDecision->addDays($this->faker->numberBetween(1, 100) + $objectionPeriodTermDurationInDays);
        $description = $this->faker->sentence();
        $firstTermDurationInDays = $this->faker->numberBetween(1, 100);

        $petitionCategory = PetitionCategory::factory()->recycle($department)->create();
        $petitionType = PetitionType::factory()->recycle($department)->create([
            'type' => PetitionTypeType::BEZWAAR,
        ]);
        $petitionStatus = PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        DepartmentTermTypeSetting::factory()->recycle($department)->create([
            'term_type' => TermType::FIRST,
            'field' => 'duration_in_days',
            'active' => true,
            'default_value' => $firstTermDurationInDays,
        ]);
        DepartmentTermTypeSetting::factory()->recycle($department)->create([
            'term_type' => TermType::OBJECTION_PERIOD,
            'field' => 'duration_in_days',
            'active' => true,
            'default_value' => $objectionPeriodTermDurationInDays,
        ]);

        $attributes = [
            'petition_type_id' => $petitionType->id->toString(),
            'petition_category_id' => $petitionCategory->id->toString(),
            'name' => $name,
            'date_of_entry' => $dateOfEntry->format('Y-m-d'),
            'date_appealed_decision' => $dateAppealedDecision->format('Y-m-d'),
            'description' => $description,
        ];

        $petitionCreateAction = $this->getPetitionTermsCreateAction();
        $petitionCreateAction->execute($department, new User(), $petitionType, $attributes);

        $this->assertDatabaseHas(Petition::class, [
            'petition_status_id' => $petitionStatus->id,
            'petition_type_id' => $petitionType->id,
            'petition_category_id' => $petitionCategory->id,
            'name' => $name,
            'date_of_entry' => $dateOfEntry->format('Y-m-d'),
            'description' => $description,
        ]);
        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::FIRST,
            'start_date' => $dateOfEntry->addDay()->format('Y-m-d'),
            'duration_in_days' => $firstTermDurationInDays,
            'penalty_amount_in_euros' => 0,
        ]);
        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::OBJECTION_PERIOD,
            'start_date' => $dateAppealedDecision->addDay()->format('Y-m-d'),
            'duration_in_days' => $objectionPeriodTermDurationInDays,
            'penalty_amount_in_euros' => 0,
        ]);
    }

    public function testPetitionCreateAsBeroepDoesNotCreateTerms(): void
    {
        $department = Department::factory()->create();
        $this->actingAs(User::factory()->create(['last_visited_department_id' => $department->id]));

        $name = $this->faker->word();
        $dateOfEntry = $this->faker->calendarDate();
        $description = $this->faker->sentence();

        $petitionCategory = PetitionCategory::factory()->recycle($department)->create();
        $petitionType = PetitionType::factory()->recycle($department)->create([
            'type' => PetitionTypeType::BEROEP,
        ]);
        $petitionStatus = PetitionStatus::factory()->recycle($department)->for($petitionType)->create();

        $attributes = [
            'petition_type_id' => $petitionType->id->toString(),
            'petition_category_id' => $petitionCategory->id->toString(),
            'name' => $name,
            'date_of_entry' => $dateOfEntry->format('Y-m-d'),
            'description' => $description,
        ];

        $petitionCreateAction = $this->getPetitionTermsCreateAction();
        $petitionCreateAction->execute($department, new User(), $petitionType, $attributes);

        $this->assertDatabaseHas(Petition::class, [
            'petition_status_id' => $petitionStatus->id,
            'petition_type_id' => $petitionType->id,
            'petition_category_id' => $petitionCategory->id,
            'name' => $name,
            'date_of_entry' => $dateOfEntry->format('Y-m-d'),
            'description' => $description,
        ]);
        $this->assertDatabaseMissing(PetitionTerm::class, [
            'type' => TermType::FIRST,
        ]);
    }

    private function getPetitionTermsCreateAction(): PetitionCreateAction
    {
        return $this->app->get(PetitionCreateAction::class);
    }
}
