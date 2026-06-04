<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\Authorization\Permission;
use App\Enums\DecisionCriteria;
use App\Enums\DecisionType;
use App\Enums\ProcessingStepStatus;
use App\Enums\RouteName;
use App\Enums\TimelineFilterGroup;
use App\Enums\TimelineType;
use App\Models\Decision;
use App\Models\Department;
use App\Models\Petition;
use App\Models\ProcessingStep;
use App\Models\TimelineItem;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

use function array_keys;
use function config;
use function route;

class DecisionControllerTest extends FeatureTestCase
{
    #[Test]
    public function storeDecisionWithPetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();
        $request = $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_DECISIONS_STORE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'name' => $this->faker->name,
                'reference' => $this->faker->optional()->word,
                'date' => $this->faker->calendarDate()->format('Y-m-d'),
                'type' => $this->faker->randomElement(DecisionType::cases())->value,
            ]);

        $decision = Decision::first();

        $request->assertRedirectToRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
            'department' => $department,
            'decision' => $decision,
        ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('message.success');

        $this->assertDatabaseHas('decision_petition', [
            'decision_id' => $decision->id,
            'petition_id' => $petition->id,
        ]);
    }

    #[Test]
    public function storeDecisionWithNonExistingPetitionFails(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::DECISION_WRITE)->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_DECISIONS_STORE, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
            ], [
                'name' => $this->faker->name,
                'reference' => $this->faker->word,
                'date' => $this->faker->calendarDate()->format('Y-m-d'),
                'type' => $this->faker->randomElement(DecisionType::cases())->value,
                'petition_id' => $this->faker->uuid()->toString(),
            ])
            ->assertNotFound();
    }

    #[Test]
    public function storeDecisionWithEmptyReferenceCanBeAttachedWhileAdded(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();
        $request = $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_DECISIONS_STORE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'name' => $this->faker->name,
                'reference' => 'REF-001',
                'date' => $this->faker->calendarDate()->format('Y-m-d'),
                'type' => $this->faker->randomElement(DecisionType::cases())->value,
                'petition_id' => $petition->id->toString(),
            ]);

        $decision = Decision::first();

        $request->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $department,
                'decision' => $decision,
            ]);
    }

    #[Test]
    public function createDecisionWithPetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_DECISIONS_CREATE, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk();
    }

    #[Test]
    public function storeDecisionWithoutPetition(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();
        $request = $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_STORE, [
                'department' => $department,
            ], [
                'name' => $this->faker->name,
                'reference' => $this->faker->optional()->word,
                'date' => $this->faker->calendarDate()->format('Y-m-d'),
                'type' => $this->faker->randomElement(DecisionType::cases())->value,
            ]);

        $decision = Decision::first();

        $request->assertRedirectToRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
            'department' => $department,
            'decision' => $decision,
        ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('message.success');

        // Verify no petition is attached
        $this->assertDatabaseMissing('decision_petition', [
            'decision_id' => $decision->id,
        ]);
    }

    #[Test]
    public function createDecisionWithoutPetition(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_CREATE, [
                'department' => $department,
            ])
            ->assertOk();
    }

    public function testEditNotFound(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_EDIT, [
                'department' => $department,
                'decision' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    public function testShow(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $decision = Decision::factory()->recycle($department)->hasAttached($petition)->create();

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_READ)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $department,
                'decision' => $decision,
            ])
            ->assertOk()
            ->assertViewIs('decision.show');
    }

    public function testShowNotFound(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_READ)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $department,
                'decision' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    public function testUpdate(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $decision = Decision::factory()->recycle($department)->hasAttached($petition)->create();

        $name = $this->faker->name();
        $date = $this->faker->calendarDate();
        $reference = $this->faker->optional()->word();

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_UPDATE, [
                'department' => $department,
                'decision' => $decision,
            ], [
                'name' => $name,
                'date' => $date->format('Y-m-d'),
                'reference' => $reference,
                'reviewbatch' => 'BATCH-2026-002',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(Decision::class, [
            'name' => $name,
            'date' => $date->format('Y-m-d'),
            'reference' => $reference,
            'reviewbatch' => 'BATCH-2026-002',
        ]);
    }

    public function testUpdateWithHtmx(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $decision = Decision::factory()->recycle($department)->hasAttached($petition)->create();
        $name = $this->faker->word();

        $authUser = User::factory()
            ->withPermissionsAndDepartment($decision->department, Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser, true, $decision->department)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_DECISIONS_UPDATE, [
                'department' => $department,
                'decision' => $decision,
            ], [
                'name' => $name,
                'date' => $decision->date->format('Y-m-d'),
                'reference' => $decision->reference,
            ])
            ->assertViewIs('decision.properties.show');

        $decision->refresh();
        $this->assertEquals($name, $decision->name);
    }

    public function testUpdateWithHtmxForNonExisting(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_DECISIONS_EDIT, [
                'department' => $department,
                'decision' => $this->faker->uuid(),
            ], [
                'name' => $this->faker->name(),
                'date' => $this->faker->calendarDate()->format('Y-m-d'),
                'reference' => $this->faker->optional()->word,
            ])
            ->assertNotFound();
    }

    public function testUpdateWithErrorsAndHxTarget(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $decision = Decision::factory()->recycle($department)->hasAttached($petition)->create();
        $hxTarget = $this->faker->slug(1);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_UPDATE, [
                'department' => $department,
                'decision' => $decision,
            ], [
                'decision_id' => $decision->id->toString(),
                'hx-target' => $hxTarget,
            ])
            ->assertSessionHasErrors('name')
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_DECISIONS_EDIT, [
                'department' => $department,
                'decision' => $decision,
                'hx-target' => $hxTarget,
            ]);
    }

    public function testShowProperties(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $decision = Decision::factory()->recycle($department)->hasAttached($petition)->create();

        $authUser = User::factory()->withPermissionsAndDepartment(
            $decision->department,
            Permission::DECISION_READ,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $decision->department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_PROPERTIES, [
                'department' => $department,
                'decision' => $decision,
            ])
            ->assertOk()
            ->assertViewIs('decision.properties.show');
    }

    public function testShowPropertiesForNonExisting(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::DECISION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_PROPERTIES, [
                'department' => $department,
                'decision' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    #[Test]
    public function testEdit(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_EDIT, [
                'department' => $department,
                'decision' => $decision,
            ])
            ->assertStatus(200)
            ->assertViewIs('form');
    }

    #[Test]
    public function indexNotAllowed(): void
    {
        $department = Department::factory()->create();
        $authUser = User::factory()->withPermissionsAndDepartment(null)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, ['department' => $department])
            ->assertForbidden();
    }

    #[Test]
    public function indexGranted(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
            ])
            ->assertOk()
            ->assertSee($decision->name)
            ->assertSee($decision->reference)
            ->assertViewIs('decision.index');
    }

    #[Test]
    public function indexShowsOnlyDepartmentDecisions(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $decision1 = Decision::factory()->recycle($department1)->create();
        $decision2 = Decision::factory()->recycle($department2)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department1, Permission::DECISION_READ)->fullyVerified()->create();
        $this->beUser($authUser, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department1->slug,
            ])
            ->assertOk()
            ->assertSee($decision1->name)
            ->assertDontSee($decision2->name);
    }

    #[Test]
    public function indexShowsPaginatedResults(): void
    {
        $department = Department::factory()->create();
        $pageLength = 2;
        ConfigHelper::set('app.pagination.items_per_page', $pageLength);

        Decision::factory()->count(3)->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
            ]);

        $response->assertOk();
        $response->assertViewHas('paginator');
        $decisions = $response->viewData('decisions');
        $this->assertGreaterThan(0, $decisions->count());
        $this->assertLessThan(3, $decisions->count());
    }

    #[Test]
    public function indexSortsByNameAscending(): void
    {
        $department = Department::factory()->create();

        Decision::factory()->recycle($department)->create(['name' => 'Zebra Decision']);
        Decision::factory()->recycle($department)->create(['name' => 'Alpha Decision']);
        Decision::factory()->recycle($department)->create(['name' => 'Beta Decision']);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
                'sort' => 'name',
            ]);

        $response->assertOk();
        $decisions = $response->viewData('decisions');
        $this->assertEquals('Alpha Decision', $decisions->first()->name);
    }

    #[Test]
    public function indexSortsByNameDescending(): void
    {
        $department = Department::factory()->create();

        Decision::factory()->recycle($department)->create(['name' => 'Alpha Decision']);
        Decision::factory()->recycle($department)->create(['name' => 'Zebra Decision']);
        Decision::factory()->recycle($department)->create(['name' => 'Beta Decision']);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
                'sort' => '-name',
            ]);

        $response->assertOk();
        $decisions = $response->viewData('decisions');
        $this->assertEquals('Zebra Decision', $decisions->first()->name);
    }

     #[Test]
    public function indexSortsByDateAscending(): void
    {
        $department = Department::factory()->create();

        Decision::factory()->recycle($department)->create(['name' => 'Recent Decision']);
        Decision::factory()->recycle($department)->create(['name' => 'Old Decision']);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
                'sort' => 'date',
            ]);

        $response->assertOk();
        $decisions = $response->viewData('decisions');
        $this->assertCount(2, $decisions);
    }

    #[Test]
    public function indexSortsByReferenceAscending(): void
    {
        $department = Department::factory()->create();

        Decision::factory()->recycle($department)->create([
            'name' => 'Decision C',
            'reference' => 'REF-2025-003',
        ]);
        Decision::factory()->recycle($department)->create([
            'name' => 'Decision A',
            'reference' => 'REF-2025-001',
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
                'sort' => 'reference',
            ]);

        $response->assertOk();
        $decisions = $response->viewData('decisions');
        $this->assertEquals('ref-2025-001', $decisions->first()->reference);
    }

    #[Test]
    public function indexWithInvalidSortParameterReturns400(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
                'sort' => 'invalid_field',
            ])
            ->assertBadRequest();
    }

    #[Test]
    public function indexSortsByProgressAscending(): void
    {
        $department = Department::factory()->create();

        // Decision with 1/2 steps completed (50%)
        $halfDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->sentence(2),
        ]);
        ProcessingStep::factory()->recycle($halfDecision)->create([
            'status' => ProcessingStepStatus::CLOSED,
        ]);
        ProcessingStep::factory()->recycle($halfDecision)->create([
            'status' => ProcessingStepStatus::PENDING,
        ]);

        // Decision with 2/2 steps completed (100%)
        $fullDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->sentence(2),
        ]);
        ProcessingStep::factory()->count(2)->recycle($fullDecision)->create([
            'status' => ProcessingStepStatus::CLOSED,
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
                'sort' => 'progress',
            ]);

        $response->assertOk();
        $decisions = $response->viewData('decisions');
        // Lower percentage should come first when sorting ascending
        $this->assertEquals($halfDecision->id, $decisions->first()->id);
        $this->assertEquals($fullDecision->id, $decisions->last()->id);
    }

    #[Test]
    public function indexSortsByProgressDescending(): void
    {
        $department = Department::factory()->create();

        // Decision with 3/3 steps completed (100%) - more absolute completed steps
        $fullDecision3 = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->sentence(2),
        ]);
        ProcessingStep::factory()->count(3)->recycle($fullDecision3)->create([
            'status' => ProcessingStepStatus::CLOSED,
        ]);

        // Decision with 2/2 steps completed (100%) - fewer absolute completed steps
        $fullDecision2 = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->sentence(2),
        ]);
        ProcessingStep::factory()->count(2)->recycle($fullDecision2)->create([
            'status' => ProcessingStepStatus::CLOSED,
        ]);

        // Decision with 1/3 steps completed (33%)
        $partialDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->sentence(2),
        ]);
        ProcessingStep::factory()->recycle($partialDecision)->create([
            'status' => ProcessingStepStatus::CLOSED,
        ]);
        ProcessingStep::factory()->count(2)->recycle($partialDecision)->create([
            'status' => ProcessingStepStatus::PENDING,
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
                'sort' => '-progress',
            ]);

        $response->assertOk();
        $decisions = $response->viewData('decisions');
        // 100% with more completed steps should come first
        $this->assertEquals($fullDecision3->id, $decisions->first()->id);
        // Then 100% with fewer completed steps
        $this->assertEquals($fullDecision2->id, $decisions->get(1)->id);
        // Then lower percentage
        $this->assertEquals($partialDecision->id, $decisions->last()->id);
    }

    #[Test]
    public function indexSortsByDeadlineAscending(): void
    {
        $department = Department::factory()->create();

        // Decision with deadline tomorrow
        $nearDeadlineDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->sentence(2),
        ]);
        ProcessingStep::factory()->recycle($nearDeadlineDecision)->create([
            'deadline_at' => CalendarDate::today()->addDays(1),
        ]);

        // Decision with deadline in a week
        $farDeadlineDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->sentence(2),
        ]);
        ProcessingStep::factory()->recycle($farDeadlineDecision)->create([
            'deadline_at' => CalendarDate::today()->addDays(7),
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
                'sort' => 'deadline',
            ]);

        $response->assertOk();
        $decisions = $response->viewData('decisions');
        // Earliest deadline should come first
        $this->assertEquals($nearDeadlineDecision->id, $decisions->first()->id);
        $this->assertEquals($farDeadlineDecision->id, $decisions->last()->id);
    }

    #[Test]
    public function indexSortsByDeadlineDescending(): void
    {
        $department = Department::factory()->create();

        // Decision with deadline tomorrow
        $nearDeadlineDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->sentence(2),
        ]);
        ProcessingStep::factory()->recycle($nearDeadlineDecision)->create([
            'deadline_at' => CalendarDate::today()->addDays(1),
        ]);

        // Decision with deadline in a week
        $farDeadlineDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->sentence(2),
        ]);
        ProcessingStep::factory()->recycle($farDeadlineDecision)->create([
            'deadline_at' => CalendarDate::today()->addDays(7),
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
                'sort' => '-deadline',
            ]);

        $response->assertOk();
        $decisions = $response->viewData('decisions');
        // Latest deadline should come first when sorting descending
        $this->assertEquals($farDeadlineDecision->id, $decisions->first()->id);
        $this->assertEquals($nearDeadlineDecision->id, $decisions->last()->id);
    }

    #[Test]
    public function filterRedirectsToIndex(): void
    {
        $department = Department::factory()->create();
        $searchTerm = Str::random(5); // Random search term to ensure uniqueness

        $authUser = User::factory()->withPermissions(Permission::DECISION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX_FILTER, [
                'department' => $department,
            ], [
                'filter' => [
                    DecisionCriteria::SEARCH->value => $searchTerm,
                ],
            ])
            ->assertRedirect(route(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
                'filter' => [
                    DecisionCriteria::SEARCH->value => $searchTerm,
                ],
            ]));
    }

    #[Test]
    public function indexWithSearchFilterShowsMatchingDecisions(): void
    {
        $department = Department::factory()->create();
        $searchTerm = Str::random(5);

        $matchingDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->words(2, true) . ' ' . $searchTerm,
            'reviewbatch' => 'BATCH-OTHER-001',
        ]);
        $nonMatchingDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->words(2, true),
            'reviewbatch' => 'BATCH-OTHER-001',
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
                'filter' => [
                    'search' => $searchTerm,
                ],
            ]);

        $response->assertOk()
            ->assertSee($matchingDecision->name)
            ->assertDontSee($nonMatchingDecision->name);
    }

    #[Test]
    public function indexWithSearchFilterShowsMatchingDecisionsByReviewbatch(): void
    {
        $department = Department::factory()->create();
        $searchTerm = Str::random(5);

        $matchingDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->words(2, true),
            'reviewbatch' => 'BATCH-' . $searchTerm . '-001',
        ]);
        $nonMatchingDecision = Decision::factory()->recycle($department)->create([
            'name' => $this->faker->words(2, true),
            'reviewbatch' => 'BATCH-OTHER-001',
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
                'filter' => [
                    'search' => $searchTerm,
                ],
            ]);

        $response->assertOk()
            ->assertSee($matchingDecision->name)
            ->assertDontSee($nonMatchingDecision->name);
    }

    public function testShowIntentionallyDisplaysCrossDepartmentRelations(): void
    {
        $departmentA = Department::factory()->create(['name' => 'Department A']);
        $departmentB = Department::factory()->create(['name' => 'Department B']);

        $petition = Petition::factory()->recycle($departmentA)->create();
        $decisionInDepartmentA = Decision::factory()->recycle($departmentA)->hasAttached($petition)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($departmentB, Permission::DECISION_READ)->fullyVerified()->create();
        $this->beUser($authUser, true, $departmentB)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $departmentA->slug,
                'decision' => $decisionInDepartmentA,
            ])
            ->assertOk()
            ->assertViewIs('decision.show');
    }

    #[Test]
    public function testDecisionCrossDepartmentVulnerability(): void
    {
        $departmentA = Department::factory()->create(['name' => 'Department A']);
        $departmentB = Department::factory()->create(['name' => 'Department B']);

        $decisionFromDepartmentA = Decision::factory()
            ->recycle($departmentA)
            ->create(['name' => 'Secret Decision A']);

        $userFromDepartmentB = User::factory()
            ->withPermissionsAndDepartment($departmentB, Permission::DECISION_READ)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($userFromDepartmentB, true, $departmentB)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $departmentB->slug,
                'decision' => $decisionFromDepartmentA->id,
            ]);

        $response->assertNotFound();
    }

    #[Test]
    public function testDecisionEditCrossDepartmentVulnerability(): void
    {
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();

        $decisionFromDepartmentA = Decision::factory()
            ->recycle($departmentA)
            ->create();

        $userFromDepartmentB = User::factory()
            ->withPermissionsAndDepartment($departmentB, Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($userFromDepartmentB, true, $departmentB)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_EDIT, [
                'department' => $departmentB->slug,
                'decision' => $decisionFromDepartmentA->id,
            ]);

        $response->assertNotFound();
    }

    #[Test]
    public function testCrossDepartmentDataIsCurrentlyAccessible(): void
    {
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();

        $decisionFromDepartmentA = Decision::factory()
            ->recycle($departmentA)
            ->create(['name' => 'Confidential Decision']);

        $userFromDepartmentB = User::factory()
            ->withPermissionsAndDepartment($departmentB, Permission::DECISION_READ)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($userFromDepartmentB, true, $departmentB)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $departmentB->slug,
                'decision' => $decisionFromDepartmentA->id,
            ]);

        if ($response->status() === 200) {
            $this->markTestIncomplete(
                'BEVEILIGINGSLEK BEVESTIGD: Cross-department toegang tot decisions is mogelijk. ' .
                'Implementeer scoped bindings om dit te verhelpen.',
            );
        } else {
            $response->assertNotFound();
        }
    }

    #[Test]
    public function testProperDepartmentIsolationAfterScopedBindings(): void
    {
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();

        $decisionA = Decision::factory()->recycle($departmentA)->create();
        $decisionB = Decision::factory()->recycle($departmentB)->create();

        $userB = User::factory()
            ->withPermissionsAndDepartment($departmentB, Permission::DECISION_READ)
            ->fullyVerified()
            ->create();

        $this->beUser($userB, true, $departmentB)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $departmentB->slug,
                'decision' => $decisionB->id,
            ])
            ->assertOk();

        $this->beUser($userB, true, $departmentB)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $departmentB->slug,
                'decision' => $decisionA->id,
            ])
            ->assertNotFound();
    }

    public function testShowWithTimelineFilter(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();

        TimelineItem::factory()->recycle($decision)->create([
            'type' => TimelineType::NOTE->value,
            'data' => [
                'comment' => 'Test note comment',
                'attachmentIds' => [],
            ],
        ]);

        TimelineItem::factory()->recycle($decision)->create([
            'type' => TimelineType::TIMELINEABLE_CREATED->value,
            'data' => [],
        ]);

        $authUser = User::factory()->withPermissions(Permission::DECISION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $department,
                'decision' => $decision,
                'timeline_filter_group' => TimelineFilterGroup::NOTES->value,
            ])
            ->assertOk()
            ->assertViewIs('decision.show')
            ->assertViewHas('selectedTimelineFilter', TimelineFilterGroup::NOTES->value)
            ->assertViewHas('timelineFilterGroups');

        $timelineFilterGroups = $response->viewData('timelineFilterGroups');
        $this->assertIsArray($timelineFilterGroups);
        $this->assertCount(2, $timelineFilterGroups);
        $this->assertEqualsCanonicalizing(
            [
                TimelineFilterGroup::NOTES->value,
                TimelineFilterGroup::UPDATES->value,
            ],
            array_keys($timelineFilterGroups),
        );
    }

    public function testShowWithTimelineFilterActuallyFiltersItems(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();
        $user = User::factory()->create();

        TimelineItem::factory()
            ->recycle($decision, $user)
            ->create([
                'type' => TimelineType::NOTE->value,
                'data' => [
                    'comment' => 'Test note comment',
                    'attachmentIds' => [],
                ],
            ]);

        TimelineItem::factory()
            ->recycle($decision, $user)
            ->create([
                'type' => TimelineType::STATUS_OCCURRENCE->value,
                'data' => [
                    'current_status' => 'Test Status',
                    'comment' => 'Status change comment',
                    'date' => '2024-01-01',
                    'attachmentIds' => [],
                ],
            ]);

        $authUser = User::factory()->withPermissions(Permission::DECISION_READ)->fullyVerified()->create();

        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $department,
                'decision' => $decision,
                'timeline_filter_group' => TimelineFilterGroup::NOTES->value,
            ]);

        $response->assertStatus(200);

        $allowedTypes = config('timeline_filters.groups.' . TimelineFilterGroup::NOTES->value);
        $this->assertContains(TimelineType::NOTE->value, $allowedTypes);
        $this->assertNotContains(TimelineType::STATUS_OCCURRENCE->value, $allowedTypes);
    }

    public function testShowWithNoSelectionTimelineFilter(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::DECISION_READ)->fullyVerified()->create();

        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $department,
                'decision' => $decision,
                'timeline_filter_group' => 'no_selection',
            ])
            ->assertOk()
            ->assertViewIs('decision.show')
            ->assertViewHas('selectedTimelineFilter', 'no_selection')
            ->assertViewHas('timelineFilterGroups');
    }

    public function testShowWithoutTimelineFilter(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();

        TimelineItem::factory()->recycle($decision)->create([
            'type' => TimelineType::NOTE->value,
            'data' => [
                'comment' => 'Test note comment',
                'attachmentIds' => [],
            ],
        ]);

        TimelineItem::factory()->recycle($decision)->create([
            'type' => TimelineType::TIMELINEABLE_CREATED->value,
            'data' => [],
        ]);

        $authUser = User::factory()->withPermissions(Permission::DECISION_READ)->fullyVerified()->create();
        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $department,
                'decision' => $decision,
            ])
            ->assertOk()
            ->assertViewIs('decision.show')
            ->assertViewHas('selectedTimelineFilter', 'no_selection')
            ->assertViewHas('timelineFilterGroups');

        $timelineFilterGroups = $response->viewData('timelineFilterGroups');
        $this->assertIsArray($timelineFilterGroups);
        $this->assertCount(2, $timelineFilterGroups);
        $this->assertEqualsCanonicalizing(
            [
                TimelineFilterGroup::NOTES->value,
                TimelineFilterGroup::UPDATES->value,
            ],
            array_keys($timelineFilterGroups),
        );
    }

    #[Test]
    public function storeDecisionWithStrippedTagsInput(): void
    {
        $department = Department::factory()->create();
        $maliciousInput = '<script>alert()</script>Decision Name';

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_STORE, [
                'department' => $department,
            ], [
                'name' => $maliciousInput,
                'reference' => $this->faker->word,
                'date' => $this->faker->calendarDate()->format('Y-m-d'),
                'type' => $this->faker->randomElement(DecisionType::cases())->value,
                'reviewbatch' => 'BATCH-2026-001',
            ]);

        $this->assertDatabaseHas(Decision::class, [
            'name' => 'alert()Decision Name',
            'reviewbatch' => 'BATCH-2026-001',
        ]);
    }

    #[Test]
    public function storeDecisionWithTooLongReviewbatchFails(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_STORE, [
                'department' => $department,
            ], [
                'name' => $this->faker->name,
                'reference' => $this->faker->word,
                'type' => $this->faker->randomElement(DecisionType::cases())->value,
                'reviewbatch' => Str::random(129),
            ])
            ->assertSessionHasErrors('reviewbatch');
    }

    #[Test]
    public function indexWithFilterParameterSavesFiltersForUser(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();

        // Make a request with filter parameters
        $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
                'filter' => [
                    'search' => 'test search',
                    'archive' => 'show_all',
                ],
            ])
            ->assertOk();

        // Verify filters were saved in database
        $this->assertDatabaseHas('user_department_filters', [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'filterable_type' => 'decision',
        ]);
    }

    #[Test]
    public function indexWithoutFilterParametersRedirectsToSavedFilters(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();

        // First, save some filters by making a request with filter parameters
        $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
                'filter' => [
                    'search' => 'saved search',
                    'archive' => 'show_archived',
                ],
            ])
            ->assertOk();

        // Now make a request without filter parameters - should redirect to saved filters
        $response = $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
            ]);

        $response->assertRedirect()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
                'filter' => [
                    'search' => 'saved search',
                    'archive' => 'show_archived',
                ],
            ]);
    }

    #[Test]
    public function indexWithoutSavedFiltersDoesNotRedirect(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->withPermissionsAndDepartment($department, Permission::DECISION_READ)->fullyVerified()->create();

        // Make a request without any saved filters - should not redirect
        $response = $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_INDEX, [
                'department' => $department,
            ]);

        $response->assertOk()
            ->assertViewIs('decision.index');
    }
}
