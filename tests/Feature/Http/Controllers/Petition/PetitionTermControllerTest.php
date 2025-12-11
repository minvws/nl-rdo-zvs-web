<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Enums\TermType;
use App\Models\Department;
use App\Models\DepartmentTermTypeSetting;
use App\Models\Petition;
use App\Models\PetitionTerm;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function json_encode;

class PetitionTermControllerTest extends FeatureTestCase
{
    public function testCreate(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TERMS_CREATE, [
                'department' => $department,
                'petition' => $petition,
                'termType' => $this->faker->randomElement(TermType::cases()),
            ])
            ->assertOk()
            ->assertViewIs('petition.petition-terms.create');
    }

    public function testStore(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $termType = $this->getRandomTermTypeWhereStartDateIsNotRecalculated();
        DepartmentTermTypeSetting::factory()
            ->recycle($department)
            ->count(3)
            ->sequence(
                ['field' => 'start_date'],
                ['field' => 'duration_in_days'],
                ['field' => 'penalty_amount_in_euros'],
            )
            ->create([
                'term_type' => $termType,
                'active' => true,
            ]);

        $startDate = $this->faker->calendarDate();
        $durationInDays = $this->faker->numberBetween(1, 100);
        $penaltyAmountInEuros = $this->faker->numberBetween(0, 100);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TERMS_STORE, [
                'department' => $department,
                'petition' => $petition,
                'termType' => $termType,
            ], [
                'start_date' => $startDate->format('Y-m-d'),
                'duration_in_days' => (string) $durationInDays,
                'penalty_amount_in_euros' => (string) $penaltyAmountInEuros,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => $termType->value,
            'start_date' => $startDate,
            'duration_in_days' => $durationInDays,
            'penalty_amount_in_euros' => $penaltyAmountInEuros,
        ]);
    }

    public function testStoreWithFirstTerm(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        DepartmentTermTypeSetting::factory()
            ->recycle($department)
            ->count(3)
            ->sequence(
                ['field' => 'start_date'],
                ['field' => 'duration_in_days'],
                ['field' => 'penalty_amount_in_euros'],
            )
            ->create([
                'term_type' => TermType::FIRST,
                'active' => true,
            ]);

        $startDate = $this->faker->calendarDate();
        $durationInDays = $this->faker->numberBetween(1, 100);
        $penaltyAmountInEuros = $this->faker->numberBetween(0, 100);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TERMS_STORE, [
                'department' => $department,
                'petition' => $petition,
                'termType' => TermType::FIRST,
            ], [
                'start_date' => $startDate->format('Y-m-d'),
                'duration_in_days' => (string) $durationInDays,
                'penalty_amount_in_euros' => (string) $penaltyAmountInEuros,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::FIRST->value,
            'start_date' => $startDate->format('Y-m-d'),
            'duration_in_days' => $durationInDays,
            'penalty_amount_in_euros' => $penaltyAmountInEuros,
        ]);
    }

    public function testStorePenaltyWithEndDate(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $termType = TermType::PENALTY;
        DepartmentTermTypeSetting::factory()
            ->recycle($department)
            ->count(4)
            ->sequence(
                ['field' => 'start_date', 'active' => false],
                ['field' => 'duration_in_days', 'active' => false],
                ['field' => 'penalty_amount_in_euros', 'active' => false],
                ['field' => 'end_date', 'active' => true],
            )
            ->create([
                'term_type' => $termType,
            ]);

        $durationInDays = 14;

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TERMS_STORE, [
                'department' => $department,
                'petition' => $petition,
                'termType' => $termType,
            ], [
                'end_date' => $petition->date_of_entry->addDays($durationInDays - 1)->format('Y-m-d'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => $termType->value,
            'start_date' => $petition->date_of_entry,
            'duration_in_days' => $durationInDays,
            'penalty_amount_in_euros' => 0,
        ]);
    }

    public function testStoreWithoutAnyConfiguredOption(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $termType = TermType::PENALTY;
        DepartmentTermTypeSetting::factory()
            ->recycle($department)
            ->count(4)
            ->sequence(
                ['field' => 'start_date'],
                ['field' => 'duration_in_days'],
                ['field' => 'penalty_amount_in_euros'],
                ['field' => 'end_date'],
            )
            ->create([
                'term_type' => $termType,
                'active' => false,
            ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TERMS_STORE, [
                'department' => $department,
                'petition' => $petition,
                'termType' => $termType,
            ], [
                // posting empty array
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => $termType->value,
            'start_date' => $petition->date_of_entry,
            'duration_in_days' => 0,
            'penalty_amount_in_euros' => 0,
        ]);
    }

    public function testStoreWithPenaltyTerm(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $termType = TermType::FIRST;

        DepartmentTermTypeSetting::factory()
            ->recycle($department)
            ->count(4)
            ->sequence(
                ['field' => 'start_date'],
                ['field' => 'duration_in_days'],
                ['field' => 'penalty_amount_in_euros'],
                [
                    'field' => 'penalty_terms',
                    'default_value' => json_encode([
                        [
                            'duration_in_days' => $this->faker->numberBetween(1, 100),
                            'penalty_amount_in_euros' => $this->faker->numberBetween(1, 100),
                        ],
                    ],),
                ],
            )
            ->create([
                'term_type' => $termType,
                'active' => true,
            ]);

        $startDate = $this->faker->calendarDate();
        $durationInDays = $this->faker->numberBetween(1, 100);
        $penaltyAmountInEuros = $this->faker->numberBetween(0, 100);
        $penaltyTermDurationInDays = $this->faker->numberBetween(1, 100);
        $penaltyTermPenaltyAmountInEuros = $this->faker->numberBetween(1, 100);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TERMS_STORE, [
                'department' => $department,
                'petition' => $petition,
                'termType' => $termType,
            ], [
                'start_date' => $startDate->format('Y-m-d'),
                'duration_in_days' => (string) $durationInDays,
                'penalty_amount_in_euros' => (string) $penaltyAmountInEuros,
                'penalty_terms' => [
                    [
                        'duration_in_days' => (string) $penaltyTermDurationInDays,
                        'penalty_amount_in_euros' => (string) $penaltyTermPenaltyAmountInEuros,
                    ],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => $termType->value,
            'start_date' => $startDate->format('Y-m-d'),
            'duration_in_days' => $durationInDays,
            'penalty_amount_in_euros' => $penaltyAmountInEuros,
        ]);

        $parentPtitionTerm = PetitionTerm::where('type', $termType)->firstOrFail(); // should be the only one in the database
        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::PENALTY,
            'start_date' => $startDate->addDays($durationInDays),
            'duration_in_days' => $penaltyTermDurationInDays,
            'penalty_amount_in_euros' => $penaltyTermPenaltyAmountInEuros,
            'parent_id' => $parentPtitionTerm->id,
        ]);
    }

    public function testStoreWithMultipleConfiguredPenaltyTerms(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $termType = $this->getRandomTermTypeWhereStartDateIsNotRecalculated();
        DepartmentTermTypeSetting::factory()
            ->recycle($department)
            ->count(4)
            ->sequence(
                ['field' => 'start_date'],
                ['field' => 'duration_in_days'],
                ['field' => 'penalty_amount_in_euros'],
                [
                    'field' => 'penalty_terms',
                    'default_value' => json_encode([
                        [
                            'duration_in_days' => (string) $this->faker->numberBetween(1, 100),
                            'penalty_amount_in_euros' => (string) $this->faker->numberBetween(1, 100),
                        ],
                        [
                            'duration_in_days' => (string) $this->faker->numberBetween(1, 100),
                            'penalty_amount_in_euros' => (string) $this->faker->numberBetween(1, 100),
                        ],
                        [
                            'duration_in_days' => (string) $this->faker->numberBetween(1, 100),
                            'penalty_amount_in_euros' => (string) $this->faker->numberBetween(1, 100),
                        ],
                    ]),
                ],
            )
            ->create([
                'term_type' => $termType,
                'active' => true,
            ]);

        $startDate = $this->faker->calendarDate();
        $durationInDays = $this->faker->numberBetween(1, 100);
        $penaltyAmountInEuros = $this->faker->numberBetween(1, 100);
        $penaltyTermDurationInDays = $this->faker->numberBetween(1, 100);
        $penaltyTermPenaltyAmountInEuros = $this->faker->numberBetween(1, 100);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TERMS_STORE, [
                'department' => $department,
                'petition' => $petition,
                'termType' => $termType,
            ], [
                'start_date' => $startDate->format('Y-m-d'),
                'duration_in_days' => (string) $durationInDays,
                'penalty_amount_in_euros' => (string) $penaltyAmountInEuros,
                'penalty_terms' => [
                    [
                        'duration_in_days' => (string) $penaltyTermDurationInDays,
                        'penalty_amount_in_euros' => (string) $penaltyTermPenaltyAmountInEuros,
                    ],
                    [
                        'duration_in_days' => (string) ($penaltyTermDurationInDays + 1),
                        'penalty_amount_in_euros' => (string) ($penaltyTermPenaltyAmountInEuros + 1),
                    ],
                    [
                        'duration_in_days' => (string) ($penaltyTermDurationInDays + 2),
                        'penalty_amount_in_euros' => (string) ($penaltyTermPenaltyAmountInEuros + 2),
                    ],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => $termType->value,
            'start_date' => $startDate,
            'duration_in_days' => $durationInDays,
            'penalty_amount_in_euros' => $penaltyAmountInEuros,
        ]);

        $this->assertDatabaseCount(PetitionTerm::class, 4);
        $parentPtitionTerm = PetitionTerm::where('type', $termType)->firstOrFail(); // should be the only one in the database

        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::PENALTY,
            'start_date' => $startDate->addDays($durationInDays),
            'duration_in_days' => $penaltyTermDurationInDays,
            'penalty_amount_in_euros' => $penaltyTermPenaltyAmountInEuros,
            'parent_id' => $parentPtitionTerm->id,
        ]);
        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::PENALTY,
            'start_date' => $startDate->addDays($durationInDays + $penaltyTermDurationInDays),
            'duration_in_days' => $penaltyTermDurationInDays + 1,
            'penalty_amount_in_euros' => $penaltyTermPenaltyAmountInEuros + 1,
        ]);
        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::PENALTY,
            'start_date' => $startDate->addDays($durationInDays + $penaltyTermDurationInDays + $penaltyTermDurationInDays + 1),
            'duration_in_days' => $penaltyTermDurationInDays + 2,
            'penalty_amount_in_euros' => $penaltyTermPenaltyAmountInEuros + 2,
        ]);
    }

    public function testStoreWithMultipleConfiguredPenaltyTermsWhenOnlyOneEntered(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $termType = $this->getRandomTermTypeWhereStartDateIsNotRecalculated();
        DepartmentTermTypeSetting::factory()
            ->recycle($department)
            ->count(4)
            ->sequence(
                ['field' => 'start_date'],
                ['field' => 'duration_in_days'],
                ['field' => 'penalty_amount_in_euros'],
                [
                    'field' => 'penalty_terms',
                    'default_value' => json_encode([
                        [
                            'duration_in_days' => $this->faker->numberBetween(1, 100),
                            'penalty_amount_in_euros' => $this->faker->numberBetween(1, 100),
                        ],
                        [
                            'duration_in_days' => $this->faker->numberBetween(1, 100),
                            'penalty_amount_in_euros' => $this->faker->numberBetween(1, 100),
                        ],
                        [
                            'duration_in_days' => $this->faker->numberBetween(1, 100),
                            'penalty_amount_in_euros' => $this->faker->numberBetween(1, 100),
                        ],
                    ],),
                ],
            )
            ->create([
                'term_type' => $termType,
                'active' => true,
            ]);

        $startDate = $this->faker->calendarDate();
        $durationInDays = $this->faker->numberBetween(1, 100);
        $penaltyAmountInEuros = $this->faker->numberBetween(0, 100);
        $penaltyTermDurationInDays = $this->faker->numberBetween(1, 100);
        $penaltyTermPenaltyAmountInEuros = $this->faker->numberBetween(1, 100);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TERMS_STORE, [
                'department' => $department,
                'petition' => $petition,
                'termType' => $termType,
            ], [
                'start_date' => $startDate->format('Y-m-d'),
                'duration_in_days' => (string) $durationInDays,
                'penalty_amount_in_euros' => (string) $penaltyAmountInEuros,
                'penalty_terms' => [
                    [
                        'duration_in_days' => (string) $penaltyTermDurationInDays,
                        'penalty_amount_in_euros' => (string) $penaltyTermPenaltyAmountInEuros,
                    ],
                    ['duration_in_days' => '', 'penalty_amount_in_euros' => ''],
                    ['duration_in_days' => '', 'penalty_amount_in_euros' => ''],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => $termType->value,
            'start_date' => $startDate,
            'duration_in_days' => $durationInDays,
            'penalty_amount_in_euros' => $penaltyAmountInEuros,
        ]);

        $this->assertDatabaseCount(PetitionTerm::class, 2);
        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => TermType::PENALTY,
            'start_date' => $startDate->addDays($durationInDays),
            'duration_in_days' => $penaltyTermDurationInDays,
            'penalty_amount_in_euros' => $penaltyTermPenaltyAmountInEuros,
        ]);
    }

    public function testStoreWithInactiveFields(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $termType = $this->getRandomTermTypeWhereStartDateIsNotRecalculated();
        DepartmentTermTypeSetting::factory()
            ->recycle($department)
            ->count(3)
            ->sequence(
                ['field' => 'start_date', 'active' => true],
                ['field' => 'duration_in_days', 'active' => true],
                ['field' => 'penalty_amount_in_euros', 'active' => false],
            )
            ->create([
                'term_type' => $termType,
            ]);

        $startDate = $this->faker->calendarDate();
        $durationInDays = $this->faker->numberBetween(1, 100);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TERMS_STORE, [
                'department' => $department,
                'petition' => $petition,
                'termType' => $termType,
            ], [
                'start_date' => $startDate->format('Y-m-d'),
                'duration_in_days' => (string) $durationInDays,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PetitionTerm::class, [
            'type' => $termType->value,
            'start_date' => $startDate,
            'duration_in_days' => $durationInDays,
            'penalty_amount_in_euros' => 0,
        ]);
    }

    public function testStoreWithValidationError(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $termType = $this->faker->randomElement(TermType::cases());
        DepartmentTermTypeSetting::factory()
            ->recycle($department)
            ->count(3)
            ->sequence(
                ['field' => 'start_date'],
                ['field' => 'duration_in_days'],
                ['field' => 'penalty_amount_in_euros'],
            )
            ->create([
                'term_type' => $termType,
                'active' => true,
            ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TERMS_STORE, [
                'department' => $department,
                'petition' => $petition,
                'termType' => $termType,
            ], [
                'duration_in_days' => $this->faker->numberBetween(1, 100),
                'penalty_amount_in_euros' => $this->faker->numberBetween(0, 100),
            ])
            ->assertSessionHasErrors('start_date')
            ->assertRedirect();
    }

    public function testEdit(): void
    {
        $department = Department::factory()->create();
        $petitionTerm = PetitionTerm::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TERMS_EDIT, [
                'department' => $department,
                'petition' => $petitionTerm->petition_id,
                'petitionTerm' => $petitionTerm,
            ])
            ->assertOk()
            ->assertViewIs('petition.petition-terms.edit');
    }

    public function testUpdate(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $petitionTerm = PetitionTerm::factory()->recycle($petition)->create([
            'petition_id' => $petition->id,
            'type' => $this->getRandomTermTypeWhereStartDateIsNotRecalculated(),
        ]);

        DepartmentTermTypeSetting::factory()
            ->recycle($department)
            ->count(3)
            ->sequence(
                ['field' => 'start_date', 'active' => false],
                ['field' => 'duration_in_days', 'active' => true],
                ['field' => 'penalty_amount_in_euros', 'active' => true],
            )
            ->create([
                'term_type' => $petitionTerm->type,
            ]);

        $startDate = $this->faker->calendarDate();
        $durationInDays = $this->faker->numberBetween(1, 100);
        $penaltyAmountInEuros = $this->faker->numberBetween(0, 100);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TERMS_UPDATE, [
                'department' => $department,
                'petition' => $petition,
                'petitionTerm' => $petitionTerm,
            ], [
                'start_date' => $startDate->format('Y-m-d'),
                'duration_in_days' => $durationInDays,
                'penalty_amount_in_euros' => $penaltyAmountInEuros,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PetitionTerm::class, [
            'start_date' => $startDate,
            'duration_in_days' => $durationInDays,
            'penalty_amount_in_euros' => $penaltyAmountInEuros,
        ]);
    }

    public function testUpdateOfPenaltyWithEndDate(): void
    {
        $startDate = $this->faker->calendarDate();
        $expectedNewDurationInDays = $this->faker->numberBetween(20, 30);

        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $petitionTerm = PetitionTerm::factory()->recycle($petition)->create([
            'start_date' => $startDate,
            'duration_in_days' => $this->faker->numberBetween(10, 20),
            'petition_id' => $petition->id,
            'type' => TermType::PENALTY,
        ]);

        DepartmentTermTypeSetting::factory()
            ->recycle($department)
            ->count(4)
            ->sequence(
                ['field' => 'start_date', 'active' => false],
                ['field' => 'duration_in_days', 'active' => false],
                ['field' => 'penalty_amount_in_euros', 'active' => true],
                ['field' => 'end_date', 'active' => true],
            )
            ->create([
                'term_type' => $petitionTerm->type,
            ]);

        $newEndDate = $petitionTerm->start_date->addDays($expectedNewDurationInDays - 1);
        $penaltyAmountInEuros = $this->faker->numberBetween(0, 100);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TERMS_UPDATE, [
                'department' => $department,
                'petition' => $petition,
                'petitionTerm' => $petitionTerm,
            ], [
                'penalty_amount_in_euros' => $penaltyAmountInEuros,
                'end_date' => $newEndDate->format('Y-m-d'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PetitionTerm::class, [
            'start_date' => $startDate,
            'duration_in_days' => $expectedNewDurationInDays,
            'penalty_amount_in_euros' => $penaltyAmountInEuros,
            'end_date' => $newEndDate,
        ]);
    }

    public function testDelete(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $petitionTerm = PetitionTerm::factory()->recycle($petition)->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TERMS_DELETE, [
                'department' => $department,
                'petition' => $petition,
                'petitionTerm' => $petitionTerm,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(
                RouteName::DEPARTMENTS_PETITIONS_SHOW,
                [
                    'department' => $department,
                    'petition' => $petition->id,
                ],
            );

        $this->assertDatabaseMissing(PetitionTerm::class, [
            'id' => $petitionTerm->id,
        ]);
    }

    public function testDeleteOfChildrenIsAlsoAllowed(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $petitionTerm = PetitionTerm::factory()->recycle($petition)->create(['parent_id' => PetitionTerm::factory()]);
        $petitionTerm2 = PetitionTerm::factory()->recycle($petition)->create(['parent_id' => $petitionTerm->id]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TERMS_DELETE, [
                'department' => $department,
                'petition' => $petition,
                'petitionTerm' => $petitionTerm,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing(PetitionTerm::class, [
            'id' => $petitionTerm->id,
        ]);
        $this->assertDatabaseMissing(PetitionTerm::class, [
            'id' => $petitionTerm2->id,
        ]);
    }

    public function testUpdateOfAppointmentWithApplicantWithEndDateWithoutDuration(): void
    {
        $startDate = $this->faker->calendarDate();
        $exptectedNewDurationInDays = $this->faker->numberBetween(20, 30);

        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $petitionTerm = PetitionTerm::factory()->recycle($petition)->create([
            'start_date' => $startDate,
            'duration_in_days' => 1000,
            'petition_id' => $petition->id,
            'type' => TermType::THIRD,
        ]);

        DepartmentTermTypeSetting::factory()
            ->recycle($department)
            ->count(4)
            ->sequence(
                ['field' => 'start_date', 'active' => false],
                ['field' => 'duration_in_days', 'active' => false],
                ['field' => 'penalty_amount_in_euros', 'active' => false],
                ['field' => 'end_date', 'active' => true],
            )
            ->create([
                'term_type' => $petitionTerm->type,
            ]);

        $newEndDate = $petitionTerm->start_date->addDays($exptectedNewDurationInDays - 1);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_TERMS_UPDATE, [
                'department' => $department,
                'petition' => $petition,
                'petitionTerm' => $petitionTerm,
            ], [
                'end_date' => $newEndDate->format('Y-m-d'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PetitionTerm::class, [
            'start_date' => $startDate,
            'duration_in_days' => $exptectedNewDurationInDays,
            'end_date' => $newEndDate,
        ]);
    }

    private function getRandomTermTypeWhereStartDateIsNotRecalculated(): TermType
    {
        return $this->faker->randomElement([
            TermType::FIRST,
            TermType::THIRD,
            TermType::SUSPENSION,
            TermType::SPECIFIED_ADJOURNMENT,
            TermType::PENALTY,
            TermType::OBJECTION_PERIOD,
        ]);
    }

    #[Test]
    public function testPetitionTermCrossDepartmentVulnerability(): void
    {
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();

        $petitionFromDepartmentA = Petition::factory()
            ->recycle($departmentA)
            ->create();

        $petitionTerm = PetitionTerm::factory()
            ->recycle($petitionFromDepartmentA)
            ->create(['description' => 'Secret Term']);

        $userFromDepartmentB = User::factory()
            ->withPermissionsAndDepartment($departmentB, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($userFromDepartmentB, true, $departmentB)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_TERMS_EDIT, [
                'department' => $departmentB->slug,
                'petition' => $petitionFromDepartmentA->id,
                'petitionTerm' => $petitionTerm->id,
            ]);

        $response->assertNotFound();
    }
}
