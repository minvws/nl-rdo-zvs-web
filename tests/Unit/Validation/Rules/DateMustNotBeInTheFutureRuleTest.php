<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\Rules;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\Validation\Rules\DateMustNotBeInTheFutureRule;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DateMustNotBeInTheFutureRuleTest extends TestCase
{
    private DateMustNotBeInTheFutureRule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new DateMustNotBeInTheFutureRule();
    }

    #[Test]
    public function testPassesOnAPastDate(): void
    {
        $this->assertNull($this->validateOn(CalendarDate::today()->subDay()));
    }

    #[Test]
    public function testPassesOnToday(): void
    {
        $this->assertNull($this->validateOn(CalendarDate::today()));
    }

    #[Test]
    public function testFailsOnTomorrow(): void
    {
        $result = $this->validateOn(CalendarDate::today()->addDay());

        $this->assertNotNull($result);
        $this->assertArrayHasKey('date', $result->getErrors());
    }

    private function validateOn(CalendarDate $date): ?ValidationResult
    {
        $event = new PetitionEventData(
            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
            date: $date,
            createdAt: CarbonImmutable::now(),
        );

        return $this->rule->validate($event, $this->createMock(DerivedState::class));
    }
}
