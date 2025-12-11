<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\Rules;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\Validation\Rules\DateMustNotBeInTermRule;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DateMustNotBeInTermRuleEdgeTest extends TestCase
{
    #[Test]
    public function testReturnsNullWhenDayNotFound(): void
    {
        $rule = new DateMustNotBeInTermRule([TermType::OBJECTION_PERIOD->value]);
        $event = $this->makeEvent();
        $state = $this->fakeState(null);

        $result = $rule->validate($event, $state);
        $this->assertNull($result);
    }

    #[Test]
    public function testReturnsNullWhenTermIsAllowed(): void
    {
        $rule = new DateMustNotBeInTermRule([TermType::OBJECTION_PERIOD->value]);
        $event = $this->makeEvent();
        $day = EventCalendarDay::new(CalendarDate::create('2024-01-10'), [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
        ]);
        $state = $this->fakeState($day);

        $result = $rule->validate($event, $state);
        $this->assertNull($result);
    }

    #[Test]
    public function testReturnsValidationResultWhenTermForbidden(): void
    {
        $rule = new DateMustNotBeInTermRule([TermType::OBJECTION_PERIOD->value, TermType::DECISION_PERIOD->value]);
        $event = $this->makeEvent(date: '2024-01-10', type: PetitionEventType::ADJOURNMENT);
        $day = EventCalendarDay::new(CalendarDate::create('2024-01-10'), [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
        ]);
        $state = $this->fakeState($day);

        $result = $rule->validate($event, $state);
        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertArrayHasKey('date', $result->getErrors());
    }

    private function makeEvent(string $date = '2024-01-10', PetitionEventType $type = PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED): PetitionEventData
    {
        return new PetitionEventData(
            type: $type,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::now(),
        );
    }

    private function fakeState(?EventCalendarDay $day): DerivedState
    {
        return new class ($day) extends DerivedState {
            public function __construct(private readonly ?EventCalendarDay $day)
            {
            }

            public function buildCalendar(): self
            {
                return $this;
            }

            public function findDay(CalendarDate $date): ?EventCalendarDay
            {
                return $this->day;
            }
        };
    }
}
