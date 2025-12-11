<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\Authorization\Permission;
use App\Enums\PetitionEventType;
use App\Enums\PetitionTypeType;
use App\Enums\ResultType;
use App\Enums\RouteName;
use App\Enums\SuspensionType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PenaltyData;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function now;
use function route;
use function sprintf;

final class PetitionEventWizardControllerTest extends FeatureTestCase
{
    #[Test]
    public function testStartCreatesNewWizardSessionWhenNoWizardIdProvided(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($user, true, $department)
            ->get(route(RouteName::PETITION_EVENTS_WIZARD_STEP, [
                'department' => $department,
                'petition' => $petition,
            ]));

        $response->assertOk();
        $response->assertViewIs('petition_events.partials.summary');
        $response->assertViewHas('events');
        $events = $response->viewData('events');
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertTrue($events->isEmpty());
        $response->assertViewHas('availableTypes');
    }

    #[Test]
    public function testStartLoadsExistingWizardSessionWhenWizardIdProvided(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $testEvents = WizardEventCollection::make()->add(new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create(now()->toDateString()),
            createdAt: CarbonImmutable::now(),
            duration: 42,
        ));

        Session::put(sprintf('wizard.petition.%s.events', $petition->id), $testEvents);

        $response = $this->beUser($user, true, $department)
            ->get(route(RouteName::PETITION_EVENTS_WIZARD_STEP, [
                'department' => $department,
                'petition' => $petition,
            ]));

        $response->assertOk();
        $response->assertViewIs('petition_events.partials.summary');
        $response->assertViewHas('events');
        $events = $response->viewData('events');
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertEquals(1, $events->count());
    }

    #[Test]
    public function testStartLoadsExistingEventsFromDatabaseWhenNoSessionExists(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $existingEvent = PetitionEvent::create([
            'petition_id' => $petition->id,
            'type' => PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->value,
            'date' => now()->toDateString(),
            'duration' => "42",
            'penalties' => [
                ['duration' => "10", 'amount' => "500"],
            ],
        ]);


        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($user, true, $department)
            ->get(route(RouteName::PETITION_EVENTS_WIZARD_STEP, [
                'department' => $department,
                'petition' => $petition,
            ]));

        $response->assertOk();
        $response->assertViewHas('events');

        $events = $response->viewData('events');
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertEquals(1, $events->count());
        $firstEvent = $events->all()->first();
        $this->assertEquals(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->value, $firstEvent->type->value);
        $this->assertEquals($existingEvent->date->toDateString(), $firstEvent->date->toDateString());
        $this->assertEquals(42, $firstEvent->duration);
    }

    #[Test]
    public function testStartShowsSelectedTypeFormWhenSelectedTypeProvided(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($user, true, $department)
            ->get(route(RouteName::PETITION_EVENTS_WIZARD_STEP, [
                'department' => $department,
                'petition' => $petition,
                'selected_type' => PetitionEventType::RECEIPT_OF_OBJECTION->value,
            ]));

        $response->assertOk();
        $response->assertViewIs('petition_events.partials.summary');
        $response->assertViewHas('availableTypes');
    }

    #[Test]
    public function testSelectTypeRedirectsToStartWithSelectedType(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($user, true, $department)
            ->get(route(RouteName::PETITION_EVENTS_WIZARD_CREATE, [
                'department' => $department,
                'petition' => $petition,
                'type' => PetitionEventType::HEARING_DATE->value,
            ]));

        $response->assertOk();
        $response->assertViewIs('petition_events.create');
        $response->assertViewHas('selectedType', PetitionEventType::HEARING_DATE);
    }

    #[Test]
    public function testAddEventStoresEventInSessionAndRedirects(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::PRIMARY_DECISION->value,
                'date' => now()->toDateString(),
                'duration' => 42,
            ]);

        $response->assertRedirect(route(RouteName::PETITION_EVENTS_WIZARD_STEP, [
            'department' => $department,
            'petition' => $petition,
        ]));

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        $this->assertTrue(Session::has($sessionKey));
        $events = Session::get($sessionKey);
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertEquals(1, $events->count());
        $firstEvent = $events->all()->first();
        $this->assertEquals(PetitionEventType::PRIMARY_DECISION->value, $firstEvent->type->value);
    }

    #[Test]
    public function testAddEventWithPenaltiesStoresPenaltiesInExtraData(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();


        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::PRIMARY_DECISION->value,
                'date' => now()->toDateString(),
                'duration' => 30,
                'penalties' => [
                    ['duration' => 10, 'amount' => 500],
                    ['duration' => 20, 'amount' => 1000],
                ],
            ]);

        $response->assertRedirect();
        if ($response->status() !== 302 && $response->status() !== 301) {
            $this->fail('Expected a redirect, got status ' . $response->status());
        }
        $this->assertTrue(Session::has($sessionKey), 'Session key was not set, validation may have failed');
        $events = Session::get($sessionKey);
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertEquals(1, $events->count());
        $firstEvent = $events->all()->first();
        $this->assertIsArray($firstEvent->penalties);
        $this->assertCount(2, $firstEvent->penalties);
        $this->assertEquals(10, $firstEvent->penalties[0]->duration);
        $this->assertEquals(500, $firstEvent->penalties[0]->amount);
    }

    #[Test]
    public function testAddMultipleEventsSequentially(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();


        // Add first event
        $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::PRIMARY_DECISION->value,
                'date' => now()->toDateString(),
                'duration' => 42,
            ]);

        // Add second event
        $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION->value,
                'date' => now()->addDays(5)->toDateString(),
                'duration' => 1,
            ]);

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        $events = Session::get($sessionKey);
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertEquals(2, $events->count());
        $this->assertEquals(PetitionEventType::PRIMARY_DECISION->value, $events->all()[0]->type->value);
        $this->assertEquals(PetitionEventType::RECEIPT_OF_OBJECTION->value, $events->all()[1]->type->value);
    }

    #[Test]
    public function testDeleteLastRemovesLastEventFromSession(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $testEvents = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create(now()->addDays(5)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 1,
            ));

        Session::put(sprintf('wizard.petition.%s.events', $petition->id), $testEvents);

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_DELETE_LAST, [
                'department' => $department,
                'petition' => $petition,
            ]), []);

        $response->assertRedirect(route(RouteName::PETITION_EVENTS_WIZARD_STEP, [
            'department' => $department,
            'petition' => $petition,

        ]));

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        $events = Session::get($sessionKey);
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertEquals(1, $events->count());
        $this->assertEquals(PetitionEventType::PRIMARY_DECISION->value, $events->all()[0]->type->value);
    }

    #[Test]
    public function testDeleteLastOnEmptySessionDoesNothing(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();


        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_DELETE_LAST, [
                'department' => $department,
                'petition' => $petition,
            ]), []);

        $response->assertRedirect();

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        $events = Session::get($sessionKey, WizardEventCollection::make());
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertEquals(0, $events->count());
    }

    #[Test]
    public function testStoreSavesEventsToDatabase(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $testEvents = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create(now()->addDays(5)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 1,
            ));

        Session::put(sprintf('wizard.petition.%s.events', $petition->id), $testEvents);

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_STORE, [
                'department' => $department,
                'petition' => $petition,
            ]), []);

        $response->assertRedirect();

        $this->assertDatabaseHas(PetitionEvent::class, [
            'petition_id' => $petition->id,
            'type' => PetitionEventType::PRIMARY_DECISION->value,
            'duration' => 42,
        ]);

        $this->assertDatabaseHas(PetitionEvent::class, [
            'petition_id' => $petition->id,
            'type' => PetitionEventType::RECEIPT_OF_OBJECTION->value,
            'duration' => 1,
        ]);
    }

    #[Test]
    public function testStoreDeletesExistingEventsBeforeSaving(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        // Create existing event
        $existingEvent = PetitionEvent::create([
            'petition_id' => $petition->id,
            'type' => PetitionEventType::HEARING_DATE->value,
            'date' => now()->subDays(10)->toDateString(),
            'duration' => 30,
        ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $testEvents = WizardEventCollection::make()->add(
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        );

        Session::put(sprintf('wizard.petition.%s.events', $petition->id), $testEvents);

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_STORE, [
                'department' => $department,
                'petition' => $petition,
            ]), []);

        $response->assertRedirect(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        // Old event should be deleted
        $this->assertDatabaseMissing(PetitionEvent::class, [
            'id' => $existingEvent->id,
        ]);

        // New event should exist
        $this->assertDatabaseHas(PetitionEvent::class, [
            'petition_id' => $petition->id,
            'type' => PetitionEventType::PRIMARY_DECISION->value,
            'duration' => 42,
        ]);

        // Should only have one event total
        $this->assertEquals(1, $petition->petitionEvents()->count());
    }

    #[Test]
    public function testStoreClearsWizardSession(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $testEvents = WizardEventCollection::make()->add(
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        );

        Session::put(sprintf('wizard.petition.%s.events', $petition->id), $testEvents);

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_STORE, [
                'department' => $department,
                'petition' => $petition,
            ]), []);

        $response->assertRedirect(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        // Wizard session should be cleared
        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        $this->assertFalse(Session::has($sessionKey));
    }

    #[Test]
    public function testStoreWithPenaltiesSavesExtraData(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $testEvents = WizardEventCollection::make()->add(
            new PetitionEventData(
                type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 30,
                penalties: [
                    new PenaltyData(amount: 500, duration: 10),
                    new PenaltyData(amount: 1000, duration: 20),
                ],
            ),
        );

        Session::put(sprintf('wizard.petition.%s.events', $petition->id), $testEvents);

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_STORE, [
                'department' => $department,
                'petition' => $petition,
            ]), []);

        $response->assertRedirect(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        $savedEvent = $petition->petitionEvents()->first();
        $this->assertNotNull($savedEvent);
        $this->assertNotNull($savedEvent->penalties);
        $this->assertCount(2, $savedEvent->penalties);
    }

    #[Test]
    public function testProcessEventDataFiltersOutEmptyPenalties(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();


        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::PRIMARY_DECISION->value,
                'date' => now()->toDateString(),
                'duration' => 30,
                'penalties' => [
                    ['duration' => 10, 'amount' => 500],
                    ['duration' => '', 'amount' => ''],
                    ['duration' => 20, 'amount' => 1000],
                ],
            ]);

        $response->assertRedirect();
        if ($response->status() !== 302 && $response->status() !== 301) {
            $this->fail('Expected a redirect, got status ' . $response->status());
        }
        $this->assertTrue(Session::has($sessionKey), 'Session key was not set, validation may have failed');
        $events = Session::get($sessionKey);
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertEquals(1, $events->count());
        $this->assertIsArray($events->all()[0]->penalties);
        $this->assertCount(2, $events->all()[0]->penalties);
    }

    #[Test]
    public function testAddEventWithInvalidTypeDoesNotStoreEvent(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();


        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [

                'type' => 'invalid_type_that_does_not_exist',
                'date' => now()->toDateString(),
                'duration' => 14,
            ]);

        $response->assertRedirect();

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        $events = Session::get($sessionKey, WizardEventCollection::make());
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertEquals(0, $events->count());
    }

    #[Test]
    public function testStartCreatesEmptyWizardSessionForNewPetition(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::BEZWAAR]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($user, true, $department)
            ->get(route(RouteName::PETITION_EVENTS_WIZARD_STEP, [
                'department' => $department,
                'petition' => $petition,
            ]));

        $response->assertOk();
        $response->assertViewHas('events');
        $events = $response->viewData('events');
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertTrue($events->isEmpty());
    }

    #[Test]
    public function testResetClearsSessionAndRedirectsToStart(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        // Set up a session with some events
        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        Session::put($sessionKey, WizardEventCollection::make()->add(
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ));

        $this->assertTrue(Session::has($sessionKey));

        // Call reset
        $response = $this->beUser($user, true, $department)
            ->get(route(RouteName::PETITION_EVENTS_WIZARD_RESET, [
                'department' => $department,
                'petition' => $petition,
            ]));

        $response->assertRedirect(route(RouteName::PETITION_EVENTS_WIZARD_STEP, [
            'department' => $department,
            'petition' => $petition,
        ]));

        // Session should be cleared
        $this->assertFalse(Session::has($sessionKey));
    }

    #[Test]
    public function testAddEventWithInvalidTypeDoesNotStoreToSession(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        $initialEvent = WizardEventCollection::make()->add(
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        );

        Session::put($sessionKey, $initialEvent);

        // Try to add an event with an invalid type (outside AVAILABLE_TYPES)
        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => 'invalid_event_type',
                'date' => now()->addDay()->toDateString(),
            ]);

        $response->assertRedirect();
        $events = Session::get($sessionKey);
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        // Should still only have the initial event
        $this->assertEquals(1, $events->count());
        $this->assertEquals(PetitionEventType::PRIMARY_DECISION->value, $events->all()[0]->type->value);
    }

    #[Test]
    public function testProcessEventDataWithMissingRequiredFields(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);

        Session::put($sessionKey, WizardEventCollection::make()->add(
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ));

        // Try to add an event without a type field - should fail validation
        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'date' => now()->addDay()->toDateString(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('type');
    }

    #[Test]
    public function testFinishWizardWithEmptyEventsArray(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        // Initialize wizard with empty events
        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        Session::put($sessionKey, WizardEventCollection::make());

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_STORE, [
                'department' => $department,
                'petition' => $petition,
            ]), []);

        $response->assertRedirect(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        // Petition should have no events
        $this->assertCount(0, $petition->petitionEvents);

        // Session should be cleared
        $this->assertFalse(Session::has($sessionKey));
    }

    #[Test]
    public function testFinishWizardDeletesExistingEventsBeforeCreatingNew(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        // Create existing events in the database
        PetitionEvent::create([
            'petition_id' => $petition->id,
            'type' => PetitionEventType::RECEIPT_OF_OBJECTION->value,
            'date' => now()->subDays(10)->toDateString(),
            'duration' => 30,
        ]);

        $this->assertCount(1, $petition->petitionEvents);

        // Set up wizard with new events
        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        Session::put($sessionKey, WizardEventCollection::make()->add(
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ));

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_STORE, [
                'department' => $department,
                'petition' => $petition,
            ]), []);

        $response->assertRedirect();

        // Reload petition to get fresh data
        $petition->refresh();

        // Should now have only the new event
        $this->assertCount(1, $petition->petitionEvents);
        $this->assertEquals(PetitionEventType::PRIMARY_DECISION, $petition->petitionEvents[0]->type);
    }

    #[Test]
    public function testCreateActionReturnsCorrectView(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($user, true, $department)
            ->get(route(RouteName::PETITION_EVENTS_WIZARD_CREATE, [
                'department' => $department,
                'petition' => $petition,
                'type' => PetitionEventType::PRIMARY_DECISION,
            ]));

        $response->assertOk();
        $response->assertViewIs('petition_events.create');
        $response->assertViewHas('config');
        $response->assertViewHas('selectedType', PetitionEventType::PRIMARY_DECISION);
        $response->assertViewHas('department', $department);
        $response->assertViewHas('petition', $petition);
    }

    #[Test]
    public function testProcessEventDataWithAllFieldsPresent(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        Session::put($sessionKey, WizardEventCollection::make()->add(
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ));

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION->value,
                'date' => now()->addDay()->toDateString(),
                'duration' => 30,
                'penalties' => [
                    ['duration' => 10, 'amount' => 500],
                    ['duration' => 20, 'amount' => 1000],
                ],
            ]);

        $response->assertRedirect();
        $events = Session::get($sessionKey);
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertEquals(2, $events->count());
        $this->assertEquals(PetitionEventType::RECEIPT_OF_OBJECTION->value, $events->all()[1]->type->value);
        $this->assertIsArray($events->all()[1]->penalties);
        $this->assertCount(2, $events->all()[1]->penalties);
    }

    #[Test]
    public function testAddReceiptOfObjectionEvent(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        // First add PRIMARY_DECISION (required for RECEIPT_OF_OBJECTION)
        Session::put(sprintf('wizard.petition.%s.events', $petition->id), WizardEventCollection::make()->add(
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ));

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION->value,
                'date' => now()->addDay()->toDateString(),
                'duration' => 42,
            ]);

        $response->assertRedirect();
        if ($response->status() !== 302 && $response->status() !== 301) {
            $this->fail('Expected a redirect, got status ' . $response->status());
        }
        $events = Session::get($sessionKey);
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertNotNull($events);
        $this->assertEquals(2, $events->count());
        $this->assertEquals(PetitionEventType::RECEIPT_OF_OBJECTION->value, $events->all()[1]->type->value);
    }

    #[Test]
    public function testAddLetterOfSuspensionSentEvent(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        // First add PRIMARY_DECISION
        Session::put(sprintf('wizard.petition.%s.events', $petition->id), WizardEventCollection::make()->add(
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ));

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::LETTER_OF_SUSPENSION_SENT->value,
                'date' => now()->addDay()->toDateString(),
                'duration' => 10,
            ]);

        $response->assertRedirect();
        if ($response->status() !== 302 && $response->status() !== 301) {
            $this->fail('Expected a redirect, got status ' . $response->status());
        }
        $events = Session::get($sessionKey);
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertNotNull($events);
    }

    #[Test]
    public function testAddCommitteeHearingScheduledEvent(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        // Add PRIMARY_DECISION and RECEIPT_OF_OBJECTION first (required for BNT)
        Session::put(sprintf('wizard.petition.%s.events', $petition->id), WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create(now()->addDay()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            )));

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::MEETING_SCHEDULED->value,
                'date' => now()->addDays(5)->toDateString(),
            ]);

        $response->assertRedirect();
        if ($response->status() !== 302 && $response->status() !== 301) {
            $this->fail('Expected a redirect, got status ' . $response->status());
        }
        $events = Session::get($sessionKey);
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertNotNull($events);
        $this->assertGreaterThanOrEqual(3, $events->count());
        $this->assertEquals(PetitionEventType::MEETING_SCHEDULED->value, $events->all()[2]->type->value);
    }

    #[Test]
    public function testAddNoticeOfDefaultReceivedEvent(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        // Add PRIMARY_DECISION and RECEIPT_OF_OBJECTION first (required for IGS)
        Session::put(sprintf('wizard.petition.%s.events', $petition->id), WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create(now()->addDay()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            )));

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->value,
                'date' => now()->addDays(5)->toDateString(),
                'duration' => 30,
                'penalties' => [
                    ['duration' => 15, 'amount' => 1000],
                ],
            ]);

        $response->assertRedirect();
        if ($response->status() !== 302 && $response->status() !== 301) {
            $this->fail('Expected a redirect, got status ' . $response->status());
        }
        $events = Session::get($sessionKey);
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertNotNull($events);
    }

    #[Test]
    public function testAddAppealDecisionNotTimelyEvent(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        // Add PRIMARY_DECISION, RECEIPT_OF_OBJECTION, and NOTICE_OF_DEFAULT_RECEIVED
        Session::put(sprintf('wizard.petition.%s.events', $petition->id), WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create(now()->addDay()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create(now()->addDays(5)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 30,
                penalties: [
                    new PenaltyData(amount: 1000, duration: 15),
                ],
            )));

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::APPEAL_DECISION_NOT_TIMELY->value,
                'date' => now()->addDays(40)->toDateString(),
                'duration' => 20,
                'penalties' => [
                    ['duration' => 10, 'amount' => 500],
                ],
            ]);

        $response->assertRedirect();
        if ($response->status() !== 302 && $response->status() !== 301) {
            $this->fail('Expected a redirect, got status ' . $response->status());
        }
        $events = Session::get($sessionKey);
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertNotNull($events);
    }

    #[Test]
    public function testAddHearingDateEvent(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        // Add PRIMARY_DECISION and RECEIPT_OF_OBJECTION first (required for HEARING_DATE)
        Session::put(sprintf('wizard.petition.%s.events', $petition->id), WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create(now()->addDay()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            )));

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::HEARING_DATE->value,
                'date' => now()->addDays(5)->toDateString(),
            ]);

        $response->assertRedirect();
        if ($response->status() !== 302 && $response->status() !== 301) {
            $this->fail('Expected a redirect, got status ' . $response->status());
        }
        $events = Session::get($sessionKey);
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertNotNull($events);
        $this->assertGreaterThanOrEqual(3, $events->count());
        $this->assertEquals(PetitionEventType::HEARING_DATE->value, $events->all()[2]->type->value);
    }

    #[Test]
    public function testAddFinalResultEvent(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        // Add PRIMARY_DECISION and RECEIPT_OF_OBJECTION first (required for FINAL_RESULT)
        Session::put(sprintf('wizard.petition.%s.events', $petition->id), WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create(now()->addDay()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            )));

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::FINAL_RESULT->value,
                'date' => now()->addDays(5)->toDateString(),
                'result_type' => ResultType::FINAL_DECISION->value,
            ]);

        $response->assertRedirect();
        if ($response->status() !== 302 && $response->status() !== 301) {
            $this->fail('Expected a redirect, got status ' . $response->status());
        }
        $events = Session::get($sessionKey);
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertNotNull($events);
        $this->assertGreaterThanOrEqual(3, $events->count());
        $this->assertEquals(PetitionEventType::FINAL_RESULT->value, $events->all()[2]->type->value);
    }

    #[Test]
    public function testSuspensionEndValidator(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        // Add PRIMARY_DECISION and LETTER_OF_SUSPENSION_SENT
        Session::put(sprintf('wizard.petition.%s.events', $petition->id), WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create(now()->addDay()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 10,
                suspensionType: SuspensionType::SPECIFIED_ADJOURNMENT,
            )));

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::SUSPENSION_END->value,
                'date' => now()->addDays(5)->toDateString(),
            ]);

        $response->assertRedirect();
        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        $events = Session::get($sessionKey);
        $this->assertInstanceOf(WizardEventCollection::class, $events);
        $this->assertEquals(3, $events->count());
        $this->assertEquals(PetitionEventType::SUSPENSION_END->value, $events->all()[2]->type->value);
    }

    #[Test]
    public function testPersistMultipleEventTypes(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $testEvents = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create(now()->addDay()->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create(now()->addDays(2)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 10,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::SUSPENSION_END,
                date: CalendarDate::create(now()->addDays(8)->toDateString()),
                createdAt: CarbonImmutable::now(),
            ));

        Session::put(sprintf('wizard.petition.%s.events', $petition->id), $testEvents);

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_STORE, [
                'department' => $department,
                'petition' => $petition,
            ]), []);

        $response->assertRedirect(route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]));

        // Verify events were persisted
        $this->assertCount(4, $petition->petitionEvents);
        $this->assertEquals(PetitionEventType::PRIMARY_DECISION, $petition->petitionEvents[0]->type);
        $this->assertEquals(PetitionEventType::RECEIPT_OF_OBJECTION, $petition->petitionEvents[1]->type);
        $this->assertEquals(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $petition->petitionEvents[2]->type);
        $this->assertEquals(PetitionEventType::SUSPENSION_END, $petition->petitionEvents[3]->type);
    }

    #[Test]
    public function testEnumHasDurationMethod(): void
    {
        // Test events that have duration
        $this->assertTrue(PetitionEventType::PRIMARY_DECISION->hasDuration());
        $this->assertTrue(PetitionEventType::RECEIPT_OF_OBJECTION->hasDuration());
        $this->assertTrue(PetitionEventType::LETTER_OF_SUSPENSION_SENT->hasDuration());
        $this->assertTrue(PetitionEventType::MEETING_SCHEDULED->hasDuration());
        $this->assertTrue(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->hasDuration());
        $this->assertTrue(PetitionEventType::APPEAL_DECISION_NOT_TIMELY->hasDuration());
        $this->assertTrue(PetitionEventType::UNSPECIFIED_ADJOURNMENT->hasDuration());

        // Test events that don't have duration
        $this->assertFalse(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN->hasDuration());
        $this->assertFalse(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END->hasDuration());
        $this->assertFalse(PetitionEventType::SUSPENSION_END->hasDuration());
        $this->assertFalse(PetitionEventType::HEARING_DATE->hasDuration());
        $this->assertFalse(PetitionEventType::FINAL_RESULT->hasDuration());
    }

    #[Test]
    public function testEnumHasPenaltiesMethod(): void
    {
        // Test events that have penalties
        $this->assertTrue(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->hasPenalties());
        $this->assertTrue(PetitionEventType::APPEAL_DECISION_NOT_TIMELY->hasPenalties());

        // Test events that don't have penalties
        $this->assertFalse(PetitionEventType::PRIMARY_DECISION->hasPenalties());
        $this->assertFalse(PetitionEventType::RECEIPT_OF_OBJECTION->hasPenalties());
        $this->assertFalse(PetitionEventType::LETTER_OF_SUSPENSION_SENT->hasPenalties());
        $this->assertFalse(PetitionEventType::MEETING_SCHEDULED->hasPenalties());
        $this->assertFalse(PetitionEventType::SUSPENSION_END->hasPenalties());
        $this->assertFalse(PetitionEventType::HEARING_DATE->hasPenalties());
        $this->assertFalse(PetitionEventType::FINAL_RESULT->hasPenalties());
    }

    #[Test]
    public function testEnumLabelMethod(): void
    {
        // Test that all event types have labels
        $this->assertNotEmpty(PetitionEventType::PRIMARY_DECISION->label());
        $this->assertNotEmpty(PetitionEventType::RECEIPT_OF_OBJECTION->label());
        $this->assertNotEmpty(PetitionEventType::LETTER_OF_SUSPENSION_SENT->label());
        $this->assertNotEmpty(PetitionEventType::SUSPENSION_END->label());
        $this->assertNotEmpty(PetitionEventType::MEETING_SCHEDULED->label());
        $this->assertNotEmpty(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->label());
        $this->assertNotEmpty(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN->label());
        $this->assertNotEmpty(PetitionEventType::APPEAL_DECISION_NOT_TIMELY->label());
        $this->assertNotEmpty(PetitionEventType::HEARING_DATE->label());
        $this->assertNotEmpty(PetitionEventType::FINAL_RESULT->label());
        $this->assertNotEmpty(PetitionEventType::UNSPECIFIED_ADJOURNMENT->label());
        $this->assertNotEmpty(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END->label());
    }

    #[Test]
    public function testEnumDescriptionMethod(): void
    {
        // Test that all event types have descriptions
        $this->assertNotEmpty(PetitionEventType::PRIMARY_DECISION->description());
        $this->assertNotEmpty(PetitionEventType::RECEIPT_OF_OBJECTION->description());
        $this->assertNotEmpty(PetitionEventType::LETTER_OF_SUSPENSION_SENT->description());
        $this->assertNotEmpty(PetitionEventType::SUSPENSION_END->description());
        $this->assertNotEmpty(PetitionEventType::MEETING_SCHEDULED->description());
        $this->assertNotEmpty(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->description());
        $this->assertNotEmpty(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN->description());
        $this->assertNotEmpty(PetitionEventType::APPEAL_DECISION_NOT_TIMELY->description());
        $this->assertNotEmpty(PetitionEventType::HEARING_DATE->description());
        $this->assertNotEmpty(PetitionEventType::FINAL_RESULT->description());
        $this->assertNotEmpty(PetitionEventType::UNSPECIFIED_ADJOURNMENT->description());
        $this->assertNotEmpty(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END->description());
    }

    #[Test]
    public function testEnumRuleMethod(): void
    {
        // Test validators that return rules
        $this->assertNotNull(PetitionEventType::PRIMARY_DECISION->rule());
        $this->assertNotNull(PetitionEventType::RECEIPT_OF_OBJECTION->rule());
        $this->assertNotNull(PetitionEventType::LETTER_OF_SUSPENSION_SENT->rule());
        $this->assertNotNull(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->rule());
        $this->assertNotNull(PetitionEventType::APPEAL_DECISION_NOT_TIMELY->rule());
        $this->assertNotNull(PetitionEventType::SUSPENSION_END->rule());
        $this->assertNotNull(PetitionEventType::MEETING_SCHEDULED->rule());
        $this->assertNotNull(PetitionEventType::HEARING_DATE->rule());
        $this->assertNotNull(PetitionEventType::FINAL_RESULT->rule());
        $this->assertNotNull(PetitionEventType::UNSPECIFIED_ADJOURNMENT->rule());
        $this->assertNotNull(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END->rule());

        // Test validators that return null
        $this->assertNull(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN->rule());
    }

    /**
     * @return array{department: Department, petitionType: PetitionType, petition: Petition}
     */
    private function createPetitionSetup(): array
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionTypeType::BEZWAAR]);
        $petitionStatus = PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->create([
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $petitionStatus->id,
        ]);

        return [
            'department' => $department,
            'petitionType' => $petitionType,
            'petition' => $petition,
        ];
    }
}
