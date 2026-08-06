<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\PetitionEvent;

use App\Enums\AdjournmentEndReason;
use App\Enums\Authorization\Permission;
use App\Enums\HearingForm;
use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Enums\ResultType;
use App\Enums\RouteName;
use App\Enums\SuspensionType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionStatus;
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

final class AddPetitionEventRequestTest extends FeatureTestCase
{
    #[Test]
    public function testEventDataIsRequired(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), []);

        $response->assertRedirect();
        $response->assertSessionHasErrors('type');
    }

    #[Test]
    public function testEventTypeIsRequired(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'date' => now()->toDateString(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('type');
    }

    #[Test]
    public function testEventDateIsRequired(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

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
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('date');
    }

    #[Test]
    public function testHearingFormIsRequiredForHearingDateEvents(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        Session::put($sessionKey, WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->subDays(10)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create(now()->subDays(5)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            )));

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::HEARING_DATE->value,
                'date' => now()->toDateString(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('hearing_form');
    }

    #[Test]
    public function testHearingFormAcceptsValidValueForHearingDateEvents(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        Session::put($sessionKey, WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->subDays(10)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create(now()->subDays(5)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 30,
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

        $events = Session::get($sessionKey);
        $this->assertNotNull($events);
        $lastEvent = $events->last();
        $this->assertSame(HearingForm::DIGITAL, $lastEvent->hearingForm);
    }

    #[Test]
    public function testInvalidHearingFormIsRejected(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        Session::put($sessionKey, WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->subDays(10)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create(now()->subDays(5)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            )));

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::HEARING_DATE->value,
                'date' => now()->toDateString(),
                'hearing_form' => 'not-a-hearing-form',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('hearing_form');
    }

    #[Test]
    public function testAdjournmentEndReasonIsRequiredForUnspecifiedAdjournmentEndEvents(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        Session::put($sessionKey, WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->subDays(20)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create(now()->subDays(15)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create(now()->subDays(10)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 5,
            )));

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::UNSPECIFIED_ADJOURNMENT_END->value,
                'date' => now()->subDay()->toDateString(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('reasoning');
    }

    #[Test]
    public function testReasoningAcceptsValidValueForUnspecifiedAdjournmentEndEvents(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        Session::put($sessionKey, WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->subDays(20)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create(now()->subDays(15)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create(now()->subDays(10)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 5,
            )));

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::UNSPECIFIED_ADJOURNMENT_END->value,
                'date' => now()->subDay()->toDateString(),
                'reasoning' => AdjournmentEndReason::Withdrawal->value,
            ]);

        $response->assertRedirect();

        $events = Session::get($sessionKey);
        $this->assertNotNull($events);
        $lastEvent = $events->last();
        $this->assertSame(AdjournmentEndReason::Withdrawal->value, $lastEvent->reasoning);
    }

    #[Test]
    public function testReasoningRejectsInvalidValueForUnspecifiedAdjournmentEndEvents(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        Session::put($sessionKey, WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create(now()->subDays(20)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create(now()->subDays(15)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create(now()->subDays(10)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 5,
            )));

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::UNSPECIFIED_ADJOURNMENT_END->value,
                'date' => now()->subDay()->toDateString(),
                'reasoning' => 'not-an-enum-value',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('reasoning');
    }

    #[Test]
    public function testValidEventDataPassesValidation(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

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
                'duration' => 42,
            ]);

        $response->assertRedirect();
        $events = Session::get($sessionKey);
        $this->assertNotNull($events);
    }

    #[Test]
    public function testEventValidatorIsCalledForValidEventType(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        // Try to add RECEIPT_OF_OBJECTION without PRIMARY_DECISION first
        // This should fail the custom validation from ObjectionReceiptValidator
        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION->value,
                'date' => now()->toDateString(),
                'duration' => 30,
            ]);

        $response->assertRedirect();
        // Should have errors from the validator
        $response->assertSessionHasErrors();
    }

    #[Test]
    public function testWithValidatorSkipsCustomValidationWhenTypeIsInvalid(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        // Try to submit with an invalid type value that won't match any enum
        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => 'invalid_type_that_does_not_exist',
                'date' => now()->toDateString(),
                'duration' => 30,
            ]);

        $response->assertRedirect();
        // Should have errors from the basic validation rules, not from custom validator
        $response->assertSessionHasErrors('type');
    }

    #[Test]
    public function testPenaltiesWithMissingAmountAreFiltered(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);

        // Empty array element is skipped by resolvePenalties
        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::PRIMARY_DECISION->value,
                'date' => now()->toDateString(),
                'duration' => 42,
                'penalties' => [
                    [],
                    ['amount' => 500, 'duration' => 10],
                ],
            ]);

        $response->assertRedirect();
        $events = Session::get($sessionKey);
        $this->assertNotNull($events);
        $lastEvent = $events->last();
        $this->assertCount(1, $lastEvent->penalties);
        $this->assertSame(500, $lastEvent->penalties[0]->amount);
    }

    #[Test]
    public function testPenaltiesWithNullValuesAreFiltered(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

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
                'duration' => 42,
                'penalties' => [
                    ['amount' => null, 'duration' => null],
                    ['amount' => 500, 'duration' => 10],
                ],
            ]);

        $response->assertRedirect();
        $events = Session::get($sessionKey);
        $this->assertNotNull($events);
        $lastEvent = $events->last();
        $this->assertCount(1, $lastEvent->penalties);
        $this->assertSame(500, $lastEvent->penalties[0]->amount);
    }

    #[Test]
    public function testEventWithSuspensionTypeSavesCorrectly(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);

        // First add required events (PRIMARY_DECISION and RECEIPT_OF_OBJECTION)
        $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::PRIMARY_DECISION->value,
                'date' => now()->subDays(10)->toDateString(),
                'duration' => 42,
            ]);

        $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION->value,
                'date' => now()->subDays(5)->toDateString(),
                'duration' => 6,
            ]);

        // Now add the suspension event
        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::LETTER_OF_SUSPENSION_SENT->value,
                'date' => now()->toDateString(),
                'duration' => 42,
                'suspension_type' => SuspensionType::SUSPENSION->value,
            ]);

        $response->assertRedirect();
        $events = Session::get($sessionKey);
        $this->assertNotNull($events);
        $lastEvent = $events->last();
        $this->assertSame(SuspensionType::SUSPENSION, $lastEvent->suspensionType);
    }

    #[Test]
    public function testReasoningIsRequiredForFinalResultOther(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup(PetitionVariant::WOO_VERZOEK);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        Session::put($sessionKey, WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PETITION_RECEIVED,
                date: CalendarDate::create(now()->subDays(2)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            )));

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::FINAL_RESULT->value,
                'date' => now()->subDay()->toDateString(),
                'result_type' => ResultType::OTHER->value,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('reasoning');
    }

    #[Test]
    public function testReasoningAcceptsValueForFinalResultOther(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup(PetitionVariant::WOO_VERZOEK);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        Session::put($sessionKey, WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PETITION_RECEIVED,
                date: CalendarDate::create(now()->subDays(2)->toDateString()),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            )));

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::FINAL_RESULT->value,
                'date' => now()->subDay()->toDateString(),
                'result_type' => ResultType::OTHER->value,
                'reasoning' => 'Custom other final result explanation',
            ]);

        $response->assertRedirect();

        $events = Session::get($sessionKey);
        $this->assertNotNull($events);
        $lastEvent = $events->last();
        $this->assertSame('Custom other final result explanation', $lastEvent->reasoning);
        $this->assertSame(ResultType::OTHER, $lastEvent->resultType);
    }

    #[Test]
    public function testTermDeadlineRequiredForMeetingScheduled(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        Session::put($sessionKey, WizardEventCollection::make()
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

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::MEETING_SCHEDULED->value,
                'date' => now()->addDays(5)->toDateString(),
                // term_deadline intentionally omitted
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('term_deadline');
    }

    #[Test]
    public function testTermDeadlineDeriveDurationForMeetingScheduled(): void
    {
        ['department' => $department, 'petition' => $petition] = $this->createPetitionSetup();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $sessionKey = sprintf('wizard.petition.%s.events', $petition->id);
        Session::put($sessionKey, WizardEventCollection::make()
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

        $eventDate = now()->addDays(5)->toDateString();
        $termDeadline = now()->addDays(19)->toDateString(); // 14 days after event date

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, [
                'department' => $department,
                'petition' => $petition,
            ]), [
                'type' => PetitionEventType::MEETING_SCHEDULED->value,
                'date' => $eventDate,
                'term_deadline' => $termDeadline,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $events = Session::get($sessionKey);
        $this->assertNotNull($events);
        $lastEvent = $events->last();
        $this->assertSame(14, $lastEvent->duration);
    }

    /**
     * @return array{department: Department, petition: Petition}
     */
    private function createPetitionSetup(PetitionVariant $petitionVariant = PetitionVariant::BEZWAAR): array
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => $petitionVariant]);
        $petitionStatus = PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->create([
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $petitionStatus->id,
        ]);

        return [
            'department' => $department,
            'petition' => $petition,
        ];
    }
}
