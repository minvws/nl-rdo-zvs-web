<?php

declare(strict_types=1);

namespace Tests\Feature\Validation;

use App\Enums\Authorization\Permission;
use App\Enums\HearingForm;
use App\Enums\PetitionEventType;
use App\Enums\PetitionTypeType;
use App\Enums\ResultType;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionType;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function now;
use function route;
use function sprintf;

class ContextAwareValidationTest extends FeatureTestCase
{
    #[Test]
    public function testFinalDecisionRequiresReceiptOfObjectionForBezwaar(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->create(['type' => PetitionTypeType::BEZWAAR]);
        $petition = Petition::factory()->for($petitionType)->for($department)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        Session::put(sprintf('wizard.petition.%s.events', $petition->id), WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::instance(now()->subDays(10)),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::instance(now()->subDays(5)),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            )));

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::FINAL_RESULT->value,
                'date' => now()->toDateString(),
                'result_type' => ResultType::FINAL_DECISION->value,
            ]);

        $response->assertRedirect();
        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        $collection = Session::get($sessionKey);
        $this->assertNotNull($collection);
        $this->assertInstanceOf(WizardEventCollection::class, $collection);
        $events = $collection->toArray();
        $this->assertCount(3, $events);
        $this->assertEquals(PetitionEventType::FINAL_RESULT->value, $events[2]['type']);
    }

    #[Test]
    public function testFinalDecisionRequiresPetitionReceivedForWooVerzoek(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->create(['type' => PetitionTypeType::WOO_VERZOEK]);
        $petition = Petition::factory()->for($petitionType)->for($department)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        Session::put(sprintf('wizard.petition.%s.events', $petition->id), WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PETITION_RECEIVED,
                date: CalendarDate::instance(now()->subDays(10)),
                createdAt: CarbonImmutable::now(),
            )));

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::FINAL_RESULT->value,
                'date' => now()->toDateString(),
                'result_type' => ResultType::FINAL_DECISION->value,
            ]);

        $response->assertRedirect();
        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        $collection = Session::get($sessionKey);
        $this->assertNotNull($collection);
        $this->assertInstanceOf(WizardEventCollection::class, $collection);
        $events = $collection->toArray();
        $this->assertCount(2, $events);
        $this->assertEquals(PetitionEventType::FINAL_RESULT->value, $events[1]['type']);
    }

    #[Test]
    public function testFinalDecisionFailsWithoutStartingEventForBezwaar(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->create(['type' => PetitionTypeType::BEZWAAR]);
        $petition = Petition::factory()->for($petitionType)->for($department)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        Session::put(sprintf('wizard.petition.%s.events', $petition->id), []);

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::FINAL_RESULT->value,
                'date' => now()->toDateString(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    #[Test]
    public function testFinalResultFailsWithoutStartingEventForWooVerzoek(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->create(['type' => PetitionTypeType::WOO_VERZOEK]);
        $petition = Petition::factory()->for($petitionType)->for($department)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        Session::put(sprintf('wizard.petition.%s.events', $petition->id), []);

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::FINAL_RESULT->value,
                'date' => now()->toDateString(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    #[Test]
    public function testHearingDateAcceptsEitherStartingEvent(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->create(['type' => PetitionTypeType::WOO_VERZOEK]);
        $petition = Petition::factory()->for($petitionType)->for($department)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        Session::put(sprintf('wizard.petition.%s.events', $petition->id), WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PETITION_RECEIVED,
                date: CalendarDate::instance(now()->subDays(10)),
                createdAt: CarbonImmutable::now(),
            )));

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::HEARING_DATE->value,
                'date' => now()->toDateString(),
                'hearing_form' => HearingForm::DIGITAL->value,
            ]);

        $response->assertRedirect();
        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        $collection = Session::get($sessionKey);
        $this->assertNotNull($collection);
        $this->assertInstanceOf(WizardEventCollection::class, $collection);
        $events = $collection->toArray();
        $this->assertCount(2, $events);
        $this->assertEquals(PetitionEventType::HEARING_DATE->value, $events[1]['type']);
    }

    #[Test]
    public function testNoticeOfDefaultReceivedAcceptsEitherStartingEvent(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->create(['type' => PetitionTypeType::WOO_VERZOEK]);
        $petition = Petition::factory()->for($petitionType)->for($department)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        Session::put(sprintf('wizard.petition.%s.events', $petition->id), WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PETITION_RECEIVED,
                date: CalendarDate::instance(now()->subDays(50)),
                createdAt: CarbonImmutable::now(),
            )));

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->value,
                'date' => now()->toDateString(),
            ]);

        $response->assertRedirect();
        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        $collection = Session::get($sessionKey);
        $this->assertNotNull($collection);
        $this->assertInstanceOf(WizardEventCollection::class, $collection);
        $events = $collection->toArray();
        $this->assertCount(2, $events);
        $this->assertEquals(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->value, $events[1]['type']);
    }
}
