<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Services\PetitionEventAvailabilityService;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class PetitionEventAvailabilityServiceTest extends TestCase
{
    private PetitionEventAvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PetitionEventAvailabilityService();
    }

    public function testWooVerzoekNewEventsRequirePetitionReceived(): void
    {
        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, WizardEventCollection::make());
        $this->assertNotContains(PetitionEventType::OPINION_OUTSIDE_TERM, $availableTypes);
        $this->assertNotContains(PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY, $availableTypes);
        $this->assertNotContains(PetitionEventType::SENT_PARTIAL_DECISION, $availableTypes);
    }

    public function testWooVerzoekIncludesNewEventsAfterPetitionReceived(): void
    {
        $events = WizardEventCollection::make()->add(
            new PetitionEventData(
                type: PetitionEventType::PETITION_RECEIVED,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
        );
        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, $events);
        $this->assertContains(PetitionEventType::OPINION_OUTSIDE_TERM, $availableTypes);
        $this->assertNotContains(PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY, $availableTypes);
        $this->assertContains(PetitionEventType::SENT_PARTIAL_DECISION, $availableTypes);
    }

    public function testBezwaarDoesNotIncludeNewWooEvents(): void
    {
        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, WizardEventCollection::make());

        $this->assertNotContains(PetitionEventType::OPINION_OUTSIDE_TERM, $availableTypes);
        $this->assertNotContains(PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY, $availableTypes);
        $this->assertNotContains(PetitionEventType::SENT_PARTIAL_DECISION, $availableTypes);
    }

    public function testReturnsPrimaryDecisionWhenNoEventsExist(): void
    {
        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, WizardEventCollection::make());

        $this->assertCount(1, $availableTypes);
        $this->assertContains(PetitionEventType::PRIMARY_DECISION, $availableTypes);
    }

    public function testReturnsPetitionReceivedWhenNoEventsExist(): void
    {
        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, WizardEventCollection::make());

        $this->assertCount(1, $availableTypes);
        $this->assertContains(PetitionEventType::PETITION_RECEIVED, $availableTypes);
    }

    public function testOnlyReceiptOfObjectionAvailableWhenOnlyPrimaryDecisionExists(): void
    {
        $currentEvents = WizardEventCollection::make()->add(
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
        );

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $currentEvents);

        $this->assertCount(1, $availableTypes);
        $this->assertNotContains(PetitionEventType::PRIMARY_DECISION, $availableTypes);
        $this->assertContains(PetitionEventType::RECEIPT_OF_OBJECTION, $availableTypes);
    }

    public function testAllOtherEventsAvailableWhenBothPrimaryAndReceiptExist(): void
    {
        $currentEvents = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 45,
            ));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $currentEvents);

        $this->assertNotContains(PetitionEventType::PRIMARY_DECISION, $availableTypes);
        $this->assertNotContains(PetitionEventType::RECEIPT_OF_OBJECTION, $availableTypes);
        $this->assertContains(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $availableTypes);
        $this->assertContains(PetitionEventType::MEETING_SCHEDULED, $availableTypes);
        $this->assertContains(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, $availableTypes);
        $this->assertNotContains(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, $availableTypes);
        $this->assertContains(PetitionEventType::HEARING_DATE, $availableTypes);
        $this->assertContains(PetitionEventType::FINAL_RESULT, $availableTypes);
    }

    public function testAllowsMultipleSuspensions(): void
    {
        $currentEvents = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 45,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 14,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::SUSPENSION_END,
                date: CalendarDate::create('2025-01-29'),
                createdAt: CarbonImmutable::now(),
            ));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $currentEvents);

        $this->assertContains(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $availableTypes);
        // pair is closed → SUSPENSION_END must wait for a new LETTER_OF_SUSPENSION_SENT
        $this->assertNotContains(PetitionEventType::SUSPENSION_END, $availableTypes);
    }

    public function testAllowsMultipleSentPartialDecisions(): void
    {
        $currentEvents = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PETITION_RECEIVED,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::SENT_PARTIAL_DECISION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::SENT_PARTIAL_DECISION,
                date: CalendarDate::create('2025-02-01'),
                createdAt: CarbonImmutable::now(),
            ));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, $currentEvents);

        $this->assertContains(PetitionEventType::SENT_PARTIAL_DECISION, $availableTypes);
    }

    public function testExcludesFinalDecisionWhenItExists(): void
    {
        $currentEvents = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 45,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::FINAL_RESULT,
                date: CalendarDate::create('2025-03-01'),
                createdAt: CarbonImmutable::now(),
            ));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $currentEvents);

        $this->assertNotContains(PetitionEventType::FINAL_RESULT, $availableTypes);
        $this->assertNotContains(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $availableTypes);
        $this->assertNotContains(PetitionEventType::SUSPENSION_END, $availableTypes);
    }

    public function testExcludesCommitteeHearingScheduledWhenItExists(): void
    {
        $currentEvents = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 45,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::MEETING_SCHEDULED,
                date: CalendarDate::create('2025-02-01'),
                createdAt: CarbonImmutable::now(),
                duration: 14,
            ));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $currentEvents);

        $this->assertContains(PetitionEventType::MEETING_SCHEDULED, $availableTypes);
        $this->assertNotContains(PetitionEventType::RECEIPT_OF_OBJECTION, $availableTypes);
        $this->assertContains(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $availableTypes);
    }

    public function testExcludesAppealDecisionNotTimelyWhenItExists(): void
    {
        $currentEvents = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 45,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
                date: CalendarDate::create('2025-02-01'),
                createdAt: CarbonImmutable::now(),
                duration: 10,
            ));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $currentEvents);

        $this->assertNotContains(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, $availableTypes);
        $this->assertNotContains(PetitionEventType::RECEIPT_OF_OBJECTION, $availableTypes);
        $this->assertNotContains(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $availableTypes);
    }

    public function testExcludesHearingDateWhenItExists(): void
    {
        $currentEvents = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 45,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::HEARING_DATE,
                date: CalendarDate::create('2025-02-15'),
                createdAt: CarbonImmutable::now(),
            ));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $currentEvents);

        $this->assertNotContains(PetitionEventType::HEARING_DATE, $availableTypes);
        $this->assertNotContains(PetitionEventType::RECEIPT_OF_OBJECTION, $availableTypes);
        $this->assertContains(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $availableTypes);
    }

    public function testShowsNoticeOfDefault(): void
    {
        $currentEvents = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 45,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::MEETING_SCHEDULED,
                date: CalendarDate::create('2025-02-15'),
                createdAt: CarbonImmutable::now(),
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::ADJOURNMENT,
                date: CalendarDate::create('2025-02-15'),
                createdAt: CarbonImmutable::now(),
            ));


        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $currentEvents);

        $this->assertContains(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, $availableTypes);
        $this->assertNotContains(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, $availableTypes);
    }

    public function testShowsAppealNotTimely(): void
    {
        $currentEvents = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 45,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::MEETING_SCHEDULED,
                date: CalendarDate::create('2025-02-15'),
                createdAt: CarbonImmutable::now(),
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::ADJOURNMENT,
                date: CalendarDate::create('2025-02-15'),
                createdAt: CarbonImmutable::now(),
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2026-02-15'),
                createdAt: CarbonImmutable::now(),
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN,
                date: CalendarDate::create('2026-02-20'),
                createdAt: CarbonImmutable::now(),
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY,
                date: CalendarDate::create('2026-02-25'),
                createdAt: CarbonImmutable::now(),
            ));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $currentEvents);

        $this->assertContains(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, $availableTypes);
        $this->assertContains(PetitionEventType::FINAL_RESULT, $availableTypes);
    }

    public function testReturnsEmptyArrayForBeroepType(): void
    {
        $currentEvents = WizardEventCollection::make()->add(
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
        );

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEROEP, $currentEvents);

        $this->assertCount(0, $availableTypes);
    }

    public function testReceiptOfObjectionNotAvailableWithoutPrimaryDecision(): void
    {
        $currentEvents = WizardEventCollection::make()->add(
            new PetitionEventData(
                type: PetitionEventType::MEETING_SCHEDULED,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 14,
            ),
        );

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $currentEvents);

        $this->assertNotContains(PetitionEventType::RECEIPT_OF_OBJECTION, $availableTypes);
    }

    public function testNoOtherEventsAvailableWithoutReceiptOfObjection(): void
    {
        $currentEvents = WizardEventCollection::make()->add(
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
        );

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $currentEvents);

        $this->assertNotContains(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $availableTypes);
        $this->assertNotContains(PetitionEventType::MEETING_SCHEDULED, $availableTypes);
        $this->assertNotContains(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, $availableTypes);
        $this->assertNotContains(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, $availableTypes);
        $this->assertNotContains(PetitionEventType::HEARING_DATE, $availableTypes);
        $this->assertNotContains(PetitionEventType::FINAL_RESULT, $availableTypes);
    }

    public function testSuspensionEndNotAvailableWithoutSuspension(): void
    {
        $currentEvents = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 45,
            ));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $currentEvents);

        $this->assertNotContains(PetitionEventType::SUSPENSION_END, $availableTypes);
        $this->assertContains(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $availableTypes);
    }

    public function testSuspensionEndAvailableWithSuspension(): void
    {
        $currentEvents = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 45,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create('2025-02-01'),
                createdAt: CarbonImmutable::now(),
                duration: 14,
            ));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $currentEvents);

        $this->assertContains(PetitionEventType::SUSPENSION_END, $availableTypes);
        $this->assertContains(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $availableTypes);
    }

    public function testPrimaryDecisionVisibleWhenNoEventsExistForBezwaar(): void
    {
        $availableTypes = $this->service->getAvailableEventTypes(
            PetitionVariant::BEZWAAR,
            WizardEventCollection::make(),
        );

        $this->assertContains(PetitionEventType::PRIMARY_DECISION, $availableTypes);
    }

    public function testPrimaryDecisionNotVisibleWhenEventsExistForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::PRIMARY_DECISION, $availableTypes);
    }

    public function testReceiptOfObjectionVisibleWhenPrimaryDecisionExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertContains(PetitionEventType::RECEIPT_OF_OBJECTION, $availableTypes);
    }

    public function testReceiptOfObjectionNotVisibleWhenAlreadyExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::RECEIPT_OF_OBJECTION, $availableTypes);
    }

    public function testPetitionReceivedVisibleWhenNoEventsExistForWooVerzoek(): void
    {
        $availableTypes = $this->service->getAvailableEventTypes(
            PetitionVariant::WOO_VERZOEK,
            WizardEventCollection::make(),
        );

        $this->assertContains(PetitionEventType::PETITION_RECEIVED, $availableTypes);
    }

    public function testPetitionReceivedNotVisibleWhenEventsExistForWooVerzoek(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PETITION_RECEIVED, '2025-01-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, $events);

        $this->assertNotContains(PetitionEventType::PETITION_RECEIVED, $availableTypes);
    }

    public function testLetterOfSuspensionSentVisibleWhenStartpointExistsForWooVerzoek(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PETITION_RECEIVED, '2025-01-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, $events);

        $this->assertContains(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $availableTypes);
    }

    public function testLetterOfSuspensionSentStillVisibleWhenAlreadyExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2025-02-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertContains(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $availableTypes);
    }

    public function testSuspensionEndVisibleWhenLetterOfSuspensionSentExistsForWooVerzoek(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PETITION_RECEIVED, '2025-01-01'))
            ->add($this->createEvent(PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2025-02-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, $events);

        $this->assertContains(PetitionEventType::SUSPENSION_END, $availableTypes);
    }

    public function testSuspensionEndVisibleAgainAfterReopeningSuspensionForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2025-02-01'))
            ->add($this->createEvent(PetitionEventType::SUSPENSION_END, '2025-02-15'))
            ->add($this->createEvent(PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2025-03-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertContains(PetitionEventType::SUSPENSION_END, $availableTypes);
    }

    public function testMeetingScheduledVisibleWhenStartpointExistsForWooVerzoek(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PETITION_RECEIVED, '2025-01-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, $events);

        $this->assertContains(PetitionEventType::MEETING_SCHEDULED, $availableTypes);
    }

    public function testMeetingScheduledVisibleEvenWhenAlreadyExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::MEETING_SCHEDULED, '2025-02-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertContains(PetitionEventType::MEETING_SCHEDULED, $availableTypes);
    }

    public function testMeetingScheduledNotVisibleWhenNoticeOfDefaultReceivedExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-03-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::MEETING_SCHEDULED, $availableTypes);
    }

    public function testMeetingScheduledNotVisibleWhenFinalResultExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::FINAL_RESULT, '2025-03-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::MEETING_SCHEDULED, $availableTypes);
    }

    public function testAdjournmentVisibleWhenStartpointExistsForWooVerzoek(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PETITION_RECEIVED, '2025-01-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, $events);

        $this->assertContains(PetitionEventType::ADJOURNMENT, $availableTypes);
    }

    public function testAdjournmentNotVisibleWhenAlreadyExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::ADJOURNMENT, '2025-02-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::ADJOURNMENT, $availableTypes);
    }

    public function testAdjournmentNotVisibleWhenNoticeOfDefaultReceivedExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-03-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::ADJOURNMENT, $availableTypes);
    }

    public function testUnspecifiedAdjournmentVisibleWhenStartpointExistsForWooVerzoek(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PETITION_RECEIVED, '2025-01-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, $events);

        $this->assertContains(PetitionEventType::UNSPECIFIED_ADJOURNMENT, $availableTypes);
    }

    public function testUnspecifiedAdjournmentNotVisibleWhenAlreadyExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::UNSPECIFIED_ADJOURNMENT, '2025-02-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::UNSPECIFIED_ADJOURNMENT, $availableTypes);
    }

    public function testUnspecifiedAdjournmentNotVisibleWhenNoticeOfDefaultReceivedExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-03-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::UNSPECIFIED_ADJOURNMENT, $availableTypes);
    }

    public function testUnspecifiedAdjournmentEndVisibleWhenUnspecifiedAdjournmentExistsForWooVerzoek(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PETITION_RECEIVED, '2025-01-01'))
            ->add($this->createEvent(PetitionEventType::UNSPECIFIED_ADJOURNMENT, '2025-02-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, $events);

        $this->assertContains(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, $availableTypes);
    }

    public function testUnspecifiedAdjournmentEndNotVisibleWhenAlreadyExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::UNSPECIFIED_ADJOURNMENT, '2025-02-01'))
            ->add($this->createEvent(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, '2025-02-15'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, $availableTypes);
    }

    public function testUnspecifiedAdjournmentEndNotVisibleWhenNoticeOfDefaultReceivedExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::UNSPECIFIED_ADJOURNMENT, '2025-02-01'))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-03-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, $availableTypes);
    }

    public function testHearingDateNotVisibleForWooVerzoek(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PETITION_RECEIVED, '2025-01-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, $events);

        $this->assertNotContains(PetitionEventType::HEARING_DATE, $availableTypes);
    }

    public function testHearingDateNotVisibleWhenFinalResultExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::FINAL_RESULT, '2025-03-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::HEARING_DATE, $availableTypes);
    }

    public function testNoticeOfDefaultReceivedVisibleWhenStartpointExistsForWooVerzoek(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PETITION_RECEIVED, '2025-01-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, $events);

        $this->assertContains(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, $availableTypes);
    }

    public function testNoticeOfDefaultReceivedNotVisibleWhenAlreadyExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-03-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, $availableTypes);
    }

    public function testNoticeOfDefaultReceivedNotVisibleWhenFinalResultExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::FINAL_RESULT, '2025-03-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, $availableTypes);
    }

    public function testNoticeOfDefaultWithdrawnVisibleWhenLastEventIsReceivedForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-03-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertContains(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, $availableTypes);
    }

    public function testNoticeOfDefaultWithdrawnVisibleWhenLastEventIsReceivedForWooVerzoek(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PETITION_RECEIVED, '2025-01-01'))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-03-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, $events);

        $this->assertContains(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, $availableTypes);
    }

    public function testNoticeOfDefaultWithdrawnNotVisibleWhenLastEventIsNotReceivedForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, $availableTypes);
    }

    public function testNoticeOfDefaultWithdrawnNotVisibleWhenLastEventIsNotReceivedForWooVerzoek(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PETITION_RECEIVED, '2025-01-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, $events);

        $this->assertNotContains(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, $availableTypes);
    }

    public function testNoticeOfDefaultReceivedAvailableAgainAfterWithdrawalForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-03-01'))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, '2025-03-15'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertContains(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, $availableTypes);
    }

    public function testNoticeOfDefaultReceivedAvailableAgainAfterWithdrawalForWooVerzoek(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PETITION_RECEIVED, '2025-01-01'))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-03-01'))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, '2025-03-15'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, $events);

        $this->assertContains(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, $availableTypes);
    }

    public function testNoticeOfDefaultReceivedNotAvailableAgainWhenLastEventIsNotWithdrawn(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-03-01'))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, '2025-03-15'))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-04-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, $availableTypes);
    }

    public function testNoticeOfDefaultWithdrawnNotVisibleWhenAlreadyExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-03-01'))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, '2025-03-15'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, $availableTypes);
    }

    public function testNoticeOfDefaultWithdrawnNotVisibleWhenAppealDecisionNotTimelyExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-03-01'))
            ->add($this->createEvent(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, '2025-04-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, $availableTypes);
    }

    public function testNoticeOfDefaultWithdrawnNotVisibleWhenFinalResultExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-03-01'))
            ->add($this->createEvent(PetitionEventType::FINAL_RESULT, '2025-04-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, $availableTypes);
    }

    public function testAppealDecisionNotTimelyNotVisibleWhenAlreadyExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-03-01'))
            ->add($this->createEvent(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, '2025-04-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, $availableTypes);
    }

    public function testAppealDecisionNotTimelyNotVisibleWhenFinalResultExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-03-01'))
            ->add($this->createEvent(PetitionEventType::FINAL_RESULT, '2025-04-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, $availableTypes);
    }

    public function testFinalResultVisibleWhenStartpointExistsForWooVerzoek(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PETITION_RECEIVED, '2025-01-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, $events);

        $this->assertContains(PetitionEventType::FINAL_RESULT, $availableTypes);
    }

    public function testFinalResultNotVisibleWhenAlreadyExistsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::FINAL_RESULT, '2025-03-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::FINAL_RESULT, $availableTypes);
    }

    public function testBNTCanBeEnteredAfterIGSForObjection(): void
    {
        $currentEvents = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-05'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),)
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-13'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),)
            ->add(new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-04-15'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $currentEvents);

        $this->assertContains(PetitionEventType::HEARING_DATE, $availableTypes);
        $this->assertContains(PetitionEventType::FINAL_RESULT, $availableTypes);
        $this->assertContains(PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY, $availableTypes);
    }

    public function testBNTCanBeEnteredAfterIGSForWoo(): void
    {
        $currentEvents = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PETITION_RECEIVED,
                date: CalendarDate::create('2025-01-05'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),)
            ->add(new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-04-15'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::WOO_VERZOEK, $currentEvents);

        $this->assertContains(PetitionEventType::FINAL_RESULT, $availableTypes);
        $this->assertContains(PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY, $availableTypes);
    }

    public function testUnspecifiedAdjournmentNotVisibleWhileSuspensionIsOpenForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2025-02-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::UNSPECIFIED_ADJOURNMENT, $availableTypes);
    }

    public function testUnspecifiedAdjournmentAvailableAgainAfterSuspensionEndsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2025-02-01'))
            ->add($this->createEvent(PetitionEventType::SUSPENSION_END, '2025-02-15'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertContains(PetitionEventType::UNSPECIFIED_ADJOURNMENT, $availableTypes);
    }

    public function testLetterOfSuspensionSentNotVisibleWhileAdjournmentIsOpenForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::UNSPECIFIED_ADJOURNMENT, '2025-02-01'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertNotContains(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $availableTypes);
    }

    public function testLetterOfSuspensionSentAvailableAgainAfterAdjournmentEndsForBezwaar(): void
    {
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::UNSPECIFIED_ADJOURNMENT, '2025-02-01'))
            ->add($this->createEvent(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, '2025-02-15'));

        $availableTypes = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);

        $this->assertContains(PetitionEventType::LETTER_OF_SUSPENSION_SENT, $availableTypes);
    }

    public function testSuspensionEndAndAdjournmentEndNeverShownTogether(): void
    {
        // open suspension only — suspension end should show, adjournment end should not
        $suspensionOpen = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2025-02-01'));

        $available = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $suspensionOpen);
        $this->assertContains(PetitionEventType::SUSPENSION_END, $available);
        $this->assertNotContains(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, $available);

        // open adjournment only — adjournment end should show, suspension end should not
        $adjournmentOpen = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::UNSPECIFIED_ADJOURNMENT, '2025-02-01'));

        $available = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $adjournmentOpen);
        $this->assertContains(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, $available);
        $this->assertNotContains(PetitionEventType::SUSPENSION_END, $available);
    }

    public function testSuspensionEndOnlyAvailableWhenAnUnmatchedSuspensionExists(): void
    {
        // closed pair → no more suspension end available
        $events = WizardEventCollection::make()
            ->add($this->createEvent(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30))
            ->add($this->createEvent(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-15', 45))
            ->add($this->createEvent(PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2025-02-01'))
            ->add($this->createEvent(PetitionEventType::SUSPENSION_END, '2025-02-15'));

        $available = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);
        $this->assertNotContains(PetitionEventType::SUSPENSION_END, $available);

        // second suspension started → available again
        $events = $events->add($this->createEvent(PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2025-03-01'));
        $available = $this->service->getAvailableEventTypes(PetitionVariant::BEZWAAR, $events);
        $this->assertContains(PetitionEventType::SUSPENSION_END, $available);
    }

    private function createEvent(PetitionEventType $type, string $date = '2025-01-01', ?int $duration = null): PetitionEventData
    {
        return new PetitionEventData(
            type: $type,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::now(),
            duration: $duration,
        );
    }
}
