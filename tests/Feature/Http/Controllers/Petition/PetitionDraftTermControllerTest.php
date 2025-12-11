<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Enums\TermType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionDraftTerm;
use App\Models\PetitionTerm;
use App\Models\User;
use App\Services\LegalTermDeadlineCalculator;
use App\ValueObjects\CalendarDate;
use Tests\Feature\FeatureTestCase;

use function __;
use function route;

class PetitionDraftTermControllerTest extends FeatureTestCase
{
    private User $user;
    private Department $department;
    private Petition $petition;
    private CalendarDate $termStartDate;
    private CalendarDate $termEndDate;
    private string $testDescription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->department = Department::factory()->create();
        $this->petition = Petition::factory()->recycle($this->department)->create();

        // Create an existing term so draft terms can be created
        $this->termStartDate = $this->faker->calendarDate();
        $this->termEndDate = $this->termStartDate->addDays($this->faker->numberBetween(10, 60));
        $duration = $this->termStartDate->diffInDays($this->termEndDate) + 1;
        $this->testDescription = $this->faker->sentence();

        PetitionTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $this->termStartDate,
            'end_date' => $this->termEndDate,
            'duration_in_days' => $duration,
            'type' => TermType::FIRST,
        ]);
    }

    public function testCreateDraftTerm(): void
    {
        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $this->department)
            ->get(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_CREATE, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]));

        $response->assertOk()
            ->assertViewIs('petition.draft-term.create');

        // Verify the petition ID matches
        $viewData = $response->viewData('petition');
        $this->assertEquals($this->petition->id, $viewData->id);
    }

    public function testStoreDraftTermWithValidData(): void
    {
        $eventDate = $this->termEndDate->addDays($this->faker->numberBetween(30, 365));
        $withdrawalDate = $eventDate->addDays($this->faker->numberBetween(1, 30));

        $data = [
            'description' => $this->testDescription,
            'event_date' => $eventDate->format('Y-m-d'),
            'days_after_event' => $this->faker->numberBetween(10, 60),
            'date_withdrawal' => $withdrawalDate->format('Y-m-d'),
            'days_after_date_withdrawal' => $this->faker->numberBetween(5, 20),
        ];

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $this->department)
            ->post(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_STORE, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]), $data)
            ->assertRedirect(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]));

        $expectedDraftStartDate = $this->termEndDate->addDay();
        $expectedDuration = $expectedDraftStartDate->diffInDays($eventDate) + 1;

        // Since event_date <= date_withdrawal, should create terms based on event_date logic
        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $this->petition->id,
            'type' => TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT->value,
            'start_date' => $expectedDraftStartDate->format('Y-m-d'),
            'duration_in_days' => $expectedDuration,
        ]);

        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $this->petition->id,
            'type' => TermType::PENDING_TERM_AFTER_EVENT->value,
            'start_date' => $eventDate->addDay()->format('Y-m-d'),
            'duration_in_days' => $this->calculateExpectedDuration($eventDate->addDay(), $data['days_after_event']),
        ]);

        // Draft term should be deleted after conversion
        $this->assertDatabaseMissing('petition_draft_terms', [
            'petition_id' => $this->petition->id,
        ]);
    }

    public function testStoreDraftTermWithMinimalData(): void
    {
        $data = [
            'days_after_event' => $this->faker->numberBetween(10, 60),
        ];

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $this->department)
            ->post(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_STORE, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]), $data)
            ->assertRedirect(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]));

        $expectedStartDate = $this->termEndDate->addDay();

        $this->assertDatabaseHas('petition_draft_terms', [
            'petition_id' => $this->petition->id,
            'start_date' => $expectedStartDate->format('Y-m-d'),
            'days_after_event' => $data['days_after_event'],
            'description' => null,
            'event_date' => null,
            'date_withdrawal' => null,
            'days_after_date_withdrawal' => 0,
        ]);

        // Since no event_date or date_withdrawal are provided, no new petition terms should be created
        // (only the existing term from setUp should exist)
        $this->assertEquals(1, PetitionTerm::where('petition_id', $this->petition->id)->count());
    }

    public function testStoreDraftTermWithInvalidData(): void
    {
        $data = [
            'description' => $this->testDescription,
            'days_after_event' => -1,
            'event_date' => 'invalid-date',
        ];

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $this->department)
            ->post(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_STORE, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]), $data)
            // Assert only for errors that PetitionDraftTermCreateRequest validates
            ->assertSessionHasErrors(['days_after_event', 'event_date']);

        $this->assertDatabaseMissing('petition_draft_terms', [
            'petition_id' => $this->petition->id,
        ]);
    }

    public function testEditDraftTermWhenExists(): void
    {
        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $this->termStartDate,
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $this->department)
            ->get(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_EDIT, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]));

        $response->assertOk()
            ->assertViewIs('petition.draft-term.edit');

        // Verify the petition ID matches
        $viewData = $response->viewData('petition');
        $this->assertEquals($this->petition->id, $viewData->id);

        // Verify the draft term ID matches
        $viewDraftTerm = $response->viewData('draftTerm');
        $this->assertEquals($draftTerm->id, $viewDraftTerm->id);
    }

    public function testEditDraftTermWhenNotExists(): void
    {
        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $this->department)
            ->get(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_EDIT, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]))
            ->assertRedirect(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_CREATE, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]));
    }

    public function testUpdateDraftTermWhenNotExistsRedirectsToCreate(): void
    {
        // Ensure no draft term exists for $this->petition
        PetitionDraftTerm::where('petition_id', $this->petition->id)->delete();

        $data = [
            'description' => $this->testDescription,
            'days_after_event' => 10,
        ];

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $this->department)
            ->post(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_UPDATE, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]), $data);

        $response->assertRedirect(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_CREATE, [
            'department' => $this->department->slug,
            'petition' => $this->petition,
        ]));

        // Optionally, assert that the update action was not called if you have a way to mock/spy on it
        // For now, we rely on the redirect and the fact that no draft term was created/updated.
        $this->assertDatabaseMissing('petition_draft_terms', [
            'petition_id' => $this->petition->id,
            'description' => $data['description'],
        ]);
    }

    public function testUpdateDraftTermWithValidData(): void
    {
        $calculatedDraftTermStartDate = $this->termEndDate->addDay();
        $eventDate = $calculatedDraftTermStartDate->addDays($this->faker->numberBetween(30, 365));
        $withdrawalDate = $eventDate->addDays($this->faker->numberBetween(1, 30));

        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'description' => $this->testDescription,
            'start_date' => $calculatedDraftTermStartDate,
            'days_after_event' => $this->faker->numberBetween(10, 40),
            'event_date' => null,
            'date_withdrawal' => null,
        ]);

        $data = [
            'description' => $this->testDescription,
            'event_date' => $eventDate->format('Y-m-d'),
            'days_after_event' => $this->faker->numberBetween(30, 60),
            'date_withdrawal' => $withdrawalDate->format('Y-m-d'),
            'days_after_date_withdrawal' => $this->faker->numberBetween(5, 20),
        ];

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $this->department)
            ->post(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_UPDATE, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]), $data)
            ->assertRedirect(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]));

        $this->assertDatabaseMissing('petition_draft_terms', [
            'id' => $draftTerm->id,
        ]);

        $eventDate = CalendarDate::createFromFormat('Y-m-d', $data['event_date']);
        $durationForUnspecified = $calculatedDraftTermStartDate->diffInDays($eventDate) + 1;

        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $this->petition->id,
            'type' => TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT->value,
            'start_date' => $calculatedDraftTermStartDate->format('Y-m-d'),
            'duration_in_days' => $durationForUnspecified,
        ]);

        $expectedStartDateForPending = $eventDate->addDays(1);
        $this->assertDatabaseHas('petition_terms', [
            'petition_id' => $this->petition->id,
            'type' => TermType::PENDING_TERM_AFTER_EVENT->value,
            'start_date' => $expectedStartDateForPending->format('Y-m-d'),
            'duration_in_days' => $this->calculateExpectedDuration($expectedStartDateForPending, $data['days_after_event']),
        ]);
    }

    public function testUpdateDraftTermDoesNotChangeStartDate(): void
    {
        $originalStartDate = $this->termStartDate;
        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'description' => $this->testDescription,
            'start_date' => $originalStartDate,
            'days_after_event' => $this->faker->numberBetween(10, 40),
            'event_date' => null,
            'date_withdrawal' => null,
        ]);

        $data = [
            'description' => $this->testDescription,
            'start_date' => $this->termEndDate->format('Y-m-d'),
            'days_after_event' => $this->faker->numberBetween(30, 60),
        ];

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $this->department)
            ->post(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_UPDATE, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]), $data)
            ->assertRedirect(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]));

        $this->assertDatabaseHas('petition_draft_terms', [
            'id' => $draftTerm->id,
            'description' => $data['description'],
            'start_date' => $originalStartDate->format('Y-m-d'),
            'days_after_event' => $data['days_after_event'],
        ]);

        // Ensure no new petition terms were created as no conversion should have happened
        $this->assertEquals(1, PetitionTerm::where('petition_id', $this->petition->id)->count());
    }

    public function testUpdateDraftTermWithInvalidData(): void
    {
        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'description' => $this->testDescription,
            'start_date' => $this->termStartDate,
            'days_after_event' => $this->faker->numberBetween(10, 40),
        ]);

        $data = [
            'start_date' => 'invalid-date',
            'days_after_event' => -1,
            'event_date' => 'invalid-date',
        ];

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $this->department)
            ->post(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_UPDATE, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]), $data)
            ->assertSessionHasErrors(['days_after_event', 'event_date']);

        $this->assertDatabaseHas('petition_draft_terms', [
            'id' => $draftTerm->id,
            'description' => $draftTerm->description,
            'days_after_event' => $draftTerm->days_after_event,
        ]);
    }

    public function testDeleteDraftTerm(): void
    {
        $draftTerm = PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $this->termStartDate,
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $this->department)
            ->get(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_DELETE, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]))
            ->assertRedirect(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]));

        $this->assertDatabaseMissing('petition_draft_terms', [
            'id' => $draftTerm->id,
        ]);
    }

    public function testDeleteDraftTermWhenNotExists(): void
    {
        // Ensure no draft term exists for $this->petition
        PetitionDraftTerm::where('petition_id', $this->petition->id)->delete();

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $this->department)
            ->get(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_DELETE, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]));

        $response->assertRedirect(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $this->department->slug,
            'petition' => $this->petition,
        ]));

        // Assert that the delete action was not called (implicitly, as there's nothing to delete)
        // and no error occurred.
        // We can also check that no draft terms exist (which should be true from the setup)
        $this->assertDatabaseCount('petition_draft_terms', 0);
    }

    public function testStoreDraftTermWhenPetitionHasNoExistingTerms(): void
    {
        $petitionWithoutTerms = Petition::factory()->recycle($this->department)->create();
        // Ensure this petition has no terms
        $petitionWithoutTerms->petitionTerms()->delete();

        $data = [
            'days_after_event' => 30,
        ];

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $this->department)
            ->post(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_STORE, [
                'department' => $this->department->slug,
                'petition' => $petitionWithoutTerms, // Use the petition without terms
            ]), $data)
            ->assertSessionHasErrors(['petition' => __('draft_term.validation.petition_must_have_existing_terms')]);

        $this->assertDatabaseMissing('petition_draft_terms', [
            'petition_id' => $petitionWithoutTerms->id,
        ]);
    }

    public function testCannotCreateMultipleDraftTermsForSamePetition(): void
    {
        PetitionDraftTerm::factory()->create([
            'petition_id' => $this->petition->id,
            'start_date' => $this->termStartDate,
        ]);

        $data = [
            'description' => $this->testDescription,
            'start_date' => $this->termEndDate->format('Y-m-d'),
            'days_after_event' => $this->faker->numberBetween(10, 50),
        ];

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $this->department)
            ->post(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_STORE, [
                'department' => $this->department->slug,
                'petition' => $this->petition,
            ]), $data)
            ->assertSessionHasErrors();

        $this->assertEquals(1, PetitionDraftTerm::where('petition_id', $this->petition->id)->count());
    }

    public function testStoreDraftTermWithNonExistentPetition(): void
    {
        $nonExistentPetitionId = $this->faker->uuid();

        $data = [
            'days_after_event' => 30,
        ];

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $this->department)
            ->post(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_STORE, [
                'department' => $this->department->slug,
                'petition' => $nonExistentPetitionId,
            ]), $data)
            ->assertNotFound();
    }

    public function testStoreDraftTermWithInvalidPetitionUuid(): void
    {
        $data = [
            'days_after_event' => 30,
        ];

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $this->department)
            ->post(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_STORE, [
                'department' => $this->department->slug,
                'petition' => 'invalid-uuid',
            ]), $data)
            ->assertNotFound();
    }

    public function testStoreDraftTermWithCorruptedPetitionBinding(): void
    {
        // This test simulates a scenario where route model binding fails
        // and petition is not properly bound, testing the defensive check in withValidator
        $data = [
            'days_after_event' => 30,
        ];

        // Mock a request where petition binding might fail or be corrupted
        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $this->department)
            ->post(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_STORE, [
                'department' => $this->department->slug,
                'petition' => 'null',
            ]), $data);

        $response->assertNotFound();
    }

    public function testCreateDraftTermWithNonExistentPetition(): void
    {
        $nonExistentPetitionId = $this->faker->uuid();

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $this->department)
            ->get(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_CREATE, [
                'department' => $this->department->slug,
                'petition' => $nonExistentPetitionId,
            ]));

        $response->assertNotFound();
    }

    public function testEditDraftTermWithNonExistentPetition(): void
    {
        $nonExistentPetitionId = $this->faker->uuid();

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $this->department)
            ->get(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_EDIT, [
                'department' => $this->department->slug,
                'petition' => $nonExistentPetitionId,
            ]));

        $response->assertNotFound();
    }

    public function testUpdateDraftTermWithNonExistentPetition(): void
    {
        $nonExistentPetitionId = $this->faker->uuid();

        $data = [
            'days_after_event' => 30,
        ];

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $this->department)
            ->post(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_UPDATE, [
                'department' => $this->department->slug,
                'petition' => $nonExistentPetitionId,
            ]), $data);

        $response->assertNotFound();
    }

    public function testDeleteDraftTermWithNonExistentPetition(): void
    {
        $nonExistentPetitionId = $this->faker->uuid();

        $authUser = User::factory()->withPermissionsAndDepartment($this->department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $this->department)
            ->get(route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_DELETE, [
                'department' => $this->department->slug,
                'petition' => $nonExistentPetitionId,
            ]));

        $response->assertNotFound();
    }

    private function calculateExpectedDuration(CalendarDate $startDate, int $originalDuration): int
    {
        $endDate = $startDate->addDays($originalDuration - 1);
        $deadlineCalculator = $this->app->make(LegalTermDeadlineCalculator::class);
        $adjustedEndDate = $deadlineCalculator->calculate($endDate);
        return $startDate->diffInDays($adjustedEndDate) + 1;
    }
}
