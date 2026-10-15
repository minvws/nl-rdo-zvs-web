<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\PetitionEvent;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Services\DerivedState;
use App\Validation\PetitionEvent\PetitionEventRules;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function __;
use function lcfirst;

class AdjournmentValidatorTest extends TestCase
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
        $state = new DerivedState()->addEvents($events);
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
            type: PetitionEventType::ADJOURNMENT,
            date: CalendarDate::create($decisionDay->toDateString()),
            createdAt: CarbonImmutable::now(),
        );
        $result = PetitionEventRules::validate(PetitionEventType::ADJOURNMENT, $event, $state);
        $this->assertTrue($result->isValid());
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
        $state = new DerivedState()->addEvents($events);
        $event = new PetitionEventData(
            type: PetitionEventType::ADJOURNMENT,
            date: CalendarDate::create('2025-01-03'),
            createdAt: CarbonImmutable::now(),
        );
        $result = PetitionEventRules::validate(PetitionEventType::ADJOURNMENT, $event, $state);
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('date', $result->getErrors());
        $this->assertSame(
            __('term.validation.common.date_must_be_latest_event', ['event' => PetitionEventType::ADJOURNMENT->label()]),
            $result->getErrors()['date'],
        );
    }

    #[Test]
    public function testFailsWhenAdjournmentAlreadyExists(): void
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
                type: PetitionEventType::ADJOURNMENT,
                date: CalendarDate::create('2025-02-01'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);
        $state = new DerivedState()->addEvents($events);
        $event = new PetitionEventData(
            type: PetitionEventType::ADJOURNMENT,
            date: CalendarDate::create('2025-02-10'),
            createdAt: CarbonImmutable::now(),
        );
        $result = PetitionEventRules::validate(PetitionEventType::ADJOURNMENT, $event, $state);
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
        $this->assertSame(
            __('term.validation.common.only_one_event_allowed', ['event' => PetitionEventType::ADJOURNMENT->label()]),
            $result->getErrors()['general'],
        );
    }

    #[Test]
    public function testDateCannotBeInObjectionPeriod(): void
    {
        // An adjournment may only be created during the decision period (beslistermijn)
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
        $state = new DerivedState()->addEvents($events);
        $state->buildCalendar();

        $objectionDay = null;
        $lastEventDate = CalendarDate::create('2025-01-05');
        foreach ($state->getCalendar() as $day) {
            if ($day->applicableTerm === TermType::OBJECTION_PERIOD->value && $day->date->greaterThan($lastEventDate)) {
                $objectionDay = $day->date;
                break;
            }
        }
        $this->assertNotNull($objectionDay, 'Er is geen objection_period dag na het laatste event in de calendar');

        $event = new PetitionEventData(
            type: PetitionEventType::ADJOURNMENT,
            date: $objectionDay,
            createdAt: CarbonImmutable::now(),
        );
        $result = PetitionEventRules::validate(PetitionEventType::ADJOURNMENT, $event, $state);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertArrayHasKey('date', $errors);
        $this->assertSame(
            __('term.validation.common.date_must_be_in_term', [
                'event' => PetitionEventType::ADJOURNMENT->label(),
                'term' => lcfirst(__('term.decision_period.default')),
            ]),
            $errors['date'],
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
        $state = new DerivedState()->addEvents($events);
        $state->buildCalendar();

        // The first day after the decision period ends: no term is active anymore.
        $lastDecisionDay = null;
        foreach ($state->getCalendar() as $day) {
            if ($day->applicableTerm === TermType::DECISION_PERIOD->value) {
                $lastDecisionDay = $day->date;
            }
        }
        $this->assertNotNull($lastDecisionDay, 'Er is geen decision_period dag in de calendar');

        $event = new PetitionEventData(
            type: PetitionEventType::ADJOURNMENT,
            date: $lastDecisionDay->addDay(),
            createdAt: CarbonImmutable::now(),
        );
        $result = PetitionEventRules::validate(PetitionEventType::ADJOURNMENT, $event, $state);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertArrayHasKey('date', $errors);
        $this->assertSame(
            __('term.validation.common.date_must_be_in_term', [
                'event' => PetitionEventType::ADJOURNMENT->label(),
                'term' => lcfirst(__('term.decision_period.default')),
            ]),
            $errors['date'],
        );
    }
}
