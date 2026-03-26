<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Terms;

use App\Actions\Terms\PetitionTermCreateAction;
use App\Enums\TermType;
use App\Models\Department;
use App\Models\DepartmentTermTypeSetting;
use App\Models\Petition;
use App\Models\PetitionTerm;
use App\Models\User;
use App\Services\LegalTermDeadlineCalculator;
use App\ValueObjects\CalendarDate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class PetitionTermCreateActionTest extends FeatureTestCase
{
    public function testPetitionFirstTermCreateWithStartDate(): void
    {
        $department = Department::factory()->create();
        $this->setActiveDepartment($department);

        $startDate = $this->faker->calendarDate();
        $durationInDays = $this->faker->numberBetween(1, 100);

        $petition = Petition::factory()->recycle($department)->create();

        $attributes = [
            'start_date' => $startDate->format('Y-m-d'),
            'duration_in_days' => (string) $durationInDays,
        ];

        $petitionTermCreateAction = $this->getPetitionTermCreateAction();
        $petitionTermCreateAction->execute($petition, TermType::FIRST, new User(), $attributes);

        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::FIRST,
            'start_date' => $startDate->format('Y-m-d'),
            'duration_in_days' => $durationInDays,
            'penalty_amount_in_euros' => 0,
        ]);
    }

    public function testPetitionSecondTermCreate(): void
    {
        $department = Department::factory()->create();
        $this->setActiveDepartment($department);

        $startDate = $this->faker->calendarDate();
        $firstTermDurationInDays = $this->faker->numberBetween(1, 100);
        $secondTermDurationInDays = $this->faker->numberBetween(1, 100);

        $petition = Petition::factory()->recycle($department)->create();
        PetitionTerm::factory()->recycle($department, $petition)->create([
            'type' => TermType::FIRST,
            'start_date' => $startDate,
            'duration_in_days' => $firstTermDurationInDays,
        ]);

        $attributes = [
            'duration_in_days' => (string) $secondTermDurationInDays,
        ];

        $petitionTermCreateAction = $this->getPetitionTermCreateAction();
        $petitionTermCreateAction->execute($petition, TermType::SECOND, new User(), $attributes);

        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::SECOND,
            'start_date' => $startDate->addDays($firstTermDurationInDays)->format('Y-m-d'),
            'duration_in_days' => $secondTermDurationInDays,
            'penalty_amount_in_euros' => 0,
        ]);
    }

    public function testPetitionNoticeOfDefaultCreate(): void
    {
        $department = Department::factory()->create();
        $this->setActiveDepartment($department);

        $entryDate = $this->faker->calendarDate();
        $durationInDays = $this->faker->numberBetween(1, 100);
        $expectedStartDate = $entryDate->addDay();
        $expectedEndDate = $this->app->get(LegalTermDeadlineCalculator::class)
            ->calculate($expectedStartDate->addDays($durationInDays - 1));

        $penaltyTerm1DurationInDays = $this->faker->numberBetween(1, 100);
        $penaltyTerm1PenaltyAmountInEuros = $this->faker->numberBetween(1, 100);
        $penaltyTerm2DurationInDays = $this->faker->numberBetween(1, 100);
        $penaltyTerm2PenaltyAmountInEuros = $this->faker->numberBetween(1, 100);

        $petition = Petition::factory()->recycle($department)->create();

        $attributes = [
            'start_date' => $entryDate->format('Y-m-d'),
            'duration_in_days' => (string) $durationInDays,
            'penalty_terms' => [
                [
                    'duration_in_days' => (string) $penaltyTerm1DurationInDays,
                    'penalty_amount_in_euros' => (string) $penaltyTerm1PenaltyAmountInEuros,
                ],
                [
                    'duration_in_days' => (string) $penaltyTerm2DurationInDays,
                    'penalty_amount_in_euros' => (string) $penaltyTerm2PenaltyAmountInEuros,
                ],
            ],
        ];

        $petitionTermCreateAction = $this->getPetitionTermCreateAction();
        $petitionTermCreateAction->execute($petition, TermType::NOTICE_OF_DEFAULT, new User(), $attributes);

        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::NOTICE_OF_DEFAULT,
            'start_date' => $expectedStartDate->format('Y-m-d'),
            'end_date' => $expectedEndDate->format('Y-m-d'),
            'duration_in_days' => $durationInDays,
            'penalty_amount_in_euros' => 0,
        ]);
        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::PENALTY,
            'start_date' => $expectedEndDate->addDay()->format('Y-m-d'),
            'duration_in_days' => $penaltyTerm1DurationInDays,
            'penalty_amount_in_euros' => $penaltyTerm1PenaltyAmountInEuros,
        ]);
        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::PENALTY,
            'start_date' => $expectedEndDate->addDays($penaltyTerm1DurationInDays)->addDay()->format('Y-m-d'),
            'duration_in_days' => $penaltyTerm2DurationInDays,
            'penalty_amount_in_euros' => $penaltyTerm2PenaltyAmountInEuros,
        ]);
    }

    #[DataProvider('petitionTypeWithDateOfMessageAlsoCreatesFirstTermDataProvider')]
    public function testPetitionTypeWithDateOfMessageAlsoCreatesFirstTerm(
        string $startDate,
        int $objectionTermDurationIndDays,
        string $dateOfMessage,
        string $expectedStartDateOfFirstTerm,
    ): void {
        $department = Department::factory()->create();
        $this->setActiveDepartment($department);
        $firstTermDurationInDays = $this->faker->numberBetween(1, 100);

        $petition = Petition::factory()->recycle($department)->create();
        DepartmentTermTypeSetting::factory()
            ->recycle($department)
            ->count(2)
            ->sequence(
                ['term_type' => TermType::OBJECTION_PERIOD, 'field' => 'date_appealed_decision'],
                ['term_type' => TermType::FIRST, 'field' => 'duration_in_days', 'default_value' => $firstTermDurationInDays],
            )
            ->create([
                'active' => true,
            ]);

        $startDate = CalendarDate::createFromFormat('d-m-Y', $startDate);

        $attributes = [
            'start_date' => $startDate->format('Y-m-d'),
            'duration_in_days' => (string) $objectionTermDurationIndDays,
            'date_appealed_decision' => CalendarDate::createFromFormat('d-m-Y', $dateOfMessage)->format('Y-m-d'),
        ];

        $petitionTermCreateAction = $this->getPetitionTermCreateAction();
        $petitionTermCreateAction->execute($petition, TermType::OBJECTION_PERIOD, new User(), $attributes);

        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::OBJECTION_PERIOD,
            'start_date' => $startDate,
            'duration_in_days' => $objectionTermDurationIndDays,
            'penalty_amount_in_euros' => 0,
        ]);
        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::FIRST,
            'start_date' => CalendarDate::createFromFormat('d-m-Y', $expectedStartDateOfFirstTerm)->format('Y-m-d'),
            'duration_in_days' => $firstTermDurationInDays,
            'penalty_amount_in_euros' => 0,
        ]);
    }

    public static function petitionTypeWithDateOfMessageAlsoCreatesFirstTermDataProvider(): array
    {
        return [
            ['1-1-2000', 10, '1-1-2000', '11-1-2000'],
            ['1-1-2000', 20, '1-1-2000', '21-1-2000'],
            ['1-1-2000', 30, '1-1-2000', '1-2-2000'],
            ['1-1-2000', 10, '21-1-2000', '22-1-2000'],
            ['1-1-2000', 20, '21-1-2000', '22-1-2000'],
            ['1-1-2000', 30, '21-1-2000', '1-2-2000'],
        ];
    }

    public function testSucceedsWhenTermEngineV2IsDisabled(): void
    {
        $this->app['config']->set('app.features.term_engine_v2', false);

        $department = Department::factory()->create();
        $this->setActiveDepartment($department);
        $petition = Petition::factory()->recycle($department)->create();

        $startDate = $this->faker->calendarDate();

        $this->getPetitionTermCreateAction()->execute($petition, TermType::FIRST, new User(), [
            'start_date' => $startDate->format('Y-m-d'),
            'duration_in_days' => '10',
        ]);

        $this->assertDatabaseHas(PetitionTerm::class, [
            'petition_id' => $petition->id,
            'type' => TermType::FIRST,
        ]);
    }

    private function getPetitionTermCreateAction(): PetitionTermCreateAction
    {
        return $this->app->get(PetitionTermCreateAction::class);
    }
}
