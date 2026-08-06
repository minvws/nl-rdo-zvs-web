<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\PetitionEvent;

use App\Enums\AdjournmentEndReason;
use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function __;

class UnspecifiedAdjournmentEndValidatorTest extends TestCase
{
    #[Test]
    public function testRequiresUnspecifiedAdjournment(): void
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
        $event = new PetitionEventData(
            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
            date: CalendarDate::create('2025-01-10'),
            createdAt: CarbonImmutable::now(),
            reasoning: AdjournmentEndReason::Event->value,
        );
        $validator = PetitionEventType::UNSPECIFIED_ADJOURNMENT_END->rule();
        $result = $validator->validate($event, $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
        $this->assertStringContainsString(
            __('term.validation.common.event_requires_dependency', [
                'event' => PetitionEventType::UNSPECIFIED_ADJOURNMENT_END->label(),
                'required_event' => PetitionEventType::UNSPECIFIED_ADJOURNMENT->label(),
            ]),
            $result->getErrors()['general'],
        );
    }

    #[Test]
    public function testAllowsAnotherEndForAnotherAdjournmentCycle(): void
    {
        // Each adjournment cycle may be ended, so more than one end event is allowed. That a
        // second end requires a matching open adjournment is enforced by the availability service.
        $events = Collection::make([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-02'),
                createdAt: CarbonImmutable::now(),
                duration: 5,
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create('2025-01-05'),
                createdAt: CarbonImmutable::now(),
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                reasoning: AdjournmentEndReason::Event->value,
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create('2025-01-20'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);
        $state = (new DerivedState())->addEvents($events);
        $event = new PetitionEventData(
            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
            date: CalendarDate::create('2025-01-25'),
            createdAt: CarbonImmutable::now(),
            reasoning: AdjournmentEndReason::Event->value,
        );
        $validator = PetitionEventType::UNSPECIFIED_ADJOURNMENT_END->rule();
        $result = $validator->validate($event, $state);

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function testDateMustBeAfterAdjournmentStart(): void
    {
        $events = Collection::make([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-02'),
                createdAt: CarbonImmutable::now(),
                duration: 5,
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create('2025-01-05'),
                createdAt: CarbonImmutable::now(),
                duration: 3,
            ),
        ]);
        $state = (new DerivedState())->addEvents($events);
        $event = new PetitionEventData(
            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
            date: CalendarDate::create('2025-01-05'),
            createdAt: CarbonImmutable::now(),
            reasoning: AdjournmentEndReason::Event->value,
        );
        $validator = PetitionEventType::UNSPECIFIED_ADJOURNMENT_END->rule();
        $result = $validator->validate($event, $state);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertArrayHasKey('date', $errors);
        $this->assertStringContainsString(
            __('term.validation.common.date_must_be_after_dependency', [
                'event' => PetitionEventType::UNSPECIFIED_ADJOURNMENT_END->label(),
                'dependency' => PetitionEventType::UNSPECIFIED_ADJOURNMENT->label(),
            ]),
            $errors['date'],
        );
    }

    #[Test]
    public function testDateMustBeLatestEvent(): void
    {
        $events = Collection::make([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-02'),
                createdAt: CarbonImmutable::now(),
                duration: 5,
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create('2025-01-05'),
                createdAt: CarbonImmutable::now(),
                duration: 3,
            ),
            new PetitionEventData(
                type: PetitionEventType::HEARING_DATE,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);
        $state = (new DerivedState())->addEvents($events);
        $event = new PetitionEventData(
            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
            date: CalendarDate::create('2025-01-10'),
            createdAt: CarbonImmutable::now(),
            reasoning: AdjournmentEndReason::Event->value,
        );
        $validator = PetitionEventType::UNSPECIFIED_ADJOURNMENT_END->rule();
        $result = $validator->validate($event, $state);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertArrayHasKey('date', $errors);
        $this->assertStringContainsString(
            __('term.validation.common.date_must_be_latest_event', ['event' => PetitionEventType::UNSPECIFIED_ADJOURNMENT_END->label()]),
            $errors['date'],
        );
    }

    #[Test]
    public function testValidAdjournmentEnd(): void
    {
        $events = Collection::make([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-02'),
                createdAt: CarbonImmutable::now(),
                duration: 5,
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create('2025-01-05'),
                createdAt: CarbonImmutable::now(),
                duration: 3,
            ),
        ]);
        $state = (new DerivedState())->addEvents($events);
        $event = new PetitionEventData(
            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
            date: CalendarDate::create('2025-01-10'),
            createdAt: CarbonImmutable::now(),
            reasoning: AdjournmentEndReason::Event->value,
        );
        $validator = PetitionEventType::UNSPECIFIED_ADJOURNMENT_END->rule();
        $result = $validator->validate($event, $state);

        $this->assertTrue($result->isValid());
    }
}
