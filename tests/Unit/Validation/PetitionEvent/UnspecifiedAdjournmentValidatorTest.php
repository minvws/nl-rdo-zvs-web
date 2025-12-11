<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\PetitionEvent;

use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Enums\TermType;
use App\Services\DerivedState;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function __;

class UnspecifiedAdjournmentValidatorTest extends TestCase
{
    #[Test]
    public function testValidAdjournment(): void
    {
        $events = Collection::make([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-02'),
                createdAt: CarbonImmutable::now(),
                duration: 5,
            ),
        ]);
        $state = (new DerivedState())->addEvents($events);
        $state->buildCalendar();
        $decisionDay = null;
        foreach ($state->getCalendar() as $day) {
            if ($day->applicableTerm === TermType::DECISION_PERIOD->value) {
                $decisionDay = $day->date;
                break;
            }
        }
        $this->assertNotNull($decisionDay, 'Er is geen decision_period dag in de calendar');
        $event = new PetitionEventData(
            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
            date: CalendarDate::create($decisionDay->toDateString()),
            createdAt: CarbonImmutable::now(),
        );
        $validator = PetitionEventType::UNSPECIFIED_ADJOURNMENT->rule();
        $result = $validator->validate($event, $state);
        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function testUniqueConstraint(): void
    {
        $events = Collection::make([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-02'),
                createdAt: CarbonImmutable::now(),
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create('2025-01-03'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);
        $state = (new DerivedState())->addEvents($events);
        $event = new PetitionEventData(
            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
            date: CalendarDate::create('2025-01-04'),
            createdAt: CarbonImmutable::now(),
        );
        $validator = PetitionEventType::UNSPECIFIED_ADJOURNMENT->rule();
        $result = $validator->validate($event, $state);
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
        $this->assertStringContainsString(
            __('term.validation.common.only_one_event_allowed', ['event' => PetitionEventType::UNSPECIFIED_ADJOURNMENT->label()]),
            $result->getErrors()['general'],
        );
    }

    #[Test]
    public function testRequiresReceiptOfObjection(): void
    {
        $events = Collection::make([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);
        $state = (new DerivedState())->addEvents($events);
        $event = new PetitionEventData(
            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
            date: CalendarDate::create('2025-01-02'),
            createdAt: CarbonImmutable::now(),
        );
        $validator = PetitionEventType::UNSPECIFIED_ADJOURNMENT->rule();
        $result = $validator->validate($event, $state);
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
        $this->assertSame(
            __('term.validation.common.event_requires_dependency_any', [
                'event' => PetitionEventType::UNSPECIFIED_ADJOURNMENT->label(),
                'required_events' => PetitionEventType::RECEIPT_OF_OBJECTION->label() . ' of ' . PetitionEventType::PETITION_RECEIVED->label(),
            ]),
            $result->getErrors()['general'],
        );
    }

    #[Test]
    public function testDateMustBeLatestEvent(): void
    {
        $events = Collection::make([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-05'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);
        $state = (new DerivedState())->addEvents($events);
        $event = new PetitionEventData(
            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
            date: CalendarDate::create('2025-01-03'),
            createdAt: CarbonImmutable::now(),
        );
        $validator = PetitionEventType::UNSPECIFIED_ADJOURNMENT->rule();
        $result = $validator->validate($event, $state);
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('date', $result->getErrors());
        $this->assertStringContainsString(
            __('term.validation.common.date_must_be_latest_event', ['event' => PetitionEventType::UNSPECIFIED_ADJOURNMENT->label()]),
            $result->getErrors()['date'],
        );
    }

    #[Test]
    public function testDateMustBeInDecisionPeriod(): void
    {
        $events = Collection::make([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-05'),
                createdAt: CarbonImmutable::now(),
                duration: 5,
            ),
        ]);
        $state = (new DerivedState())->addEvents($events);
        $state->buildCalendar();

        // Find the last objection period day (after last event date, not decision period)
        $objectionDay = null;
        $lastEventDate = CalendarDate::create('2025-01-05');
        foreach ($state->getCalendar() as $day) {
            if ($day->applicableTerm === TermType::OBJECTION_PERIOD->value && $day->date->greaterThan($lastEventDate)) {
                $objectionDay = $day->date;
            }
        }
        $this->assertNotNull($objectionDay, 'Er is geen objection_period dag na het laatste event in de calendar');

        $event = new PetitionEventData(
            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
            date: CalendarDate::create($objectionDay->toDateString()),
            createdAt: CarbonImmutable::now(),
        );
        $validator = PetitionEventType::UNSPECIFIED_ADJOURNMENT->rule();
        $result = $validator->validate($event, $state);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertArrayHasKey('date', $errors);
        $this->assertStringContainsString(
            __('term.validation.common.date_must_be_in_term', ['event' => PetitionEventType::UNSPECIFIED_ADJOURNMENT->label()]),
            $errors['date'],
        );
    }

    #[Test]
    public function testCannotBeAddedDuringSuspension(): void
    {
        $events = Collection::make([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-05'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create('2025-03-20'),
                createdAt: CarbonImmutable::now(),
                duration: 10,
                suspensionType: SuspensionType::SUSPENSION,
            ),
        ]);
        $state = (new DerivedState())->addEvents($events);
        $state->buildCalendar();

        // Find a day during suspension that's also in decision period (after last event)
        $suspensionDay = null;
        $lastEventDate = CalendarDate::create('2025-03-20');
        foreach ($state->getCalendar() as $day) {
            if (
                $day->suspensionType !== null &&
                $day->applicableTerm === TermType::DECISION_PERIOD->value &&
                $day->date->greaterThan($lastEventDate)
            ) {
                $suspensionDay = $day->date;
                break;
            }
        }
        $this->assertNotNull($suspensionDay, 'Er is geen suspension dag in decision_period na het laatste event in de calendar');

        $event = new PetitionEventData(
            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
            date: CalendarDate::create($suspensionDay->toDateString()),
            createdAt: CarbonImmutable::now(),
        );
        $validator = PetitionEventType::UNSPECIFIED_ADJOURNMENT->rule();
        $result = $validator->validate($event, $state);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertArrayHasKey('date', $errors);
        $this->assertStringContainsString(
            __('term.validation.common.date_already_in_suspension'),
            $errors['date'],
        );
    }
}
