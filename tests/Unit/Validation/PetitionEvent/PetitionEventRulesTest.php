<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\PetitionEvent;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Validation\PetitionEvent\PetitionEventRules;
use App\Validation\Rules\ValidationRuleInterface;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function __;
use function collect;

/**
 * Which rules apply to which event type is covered by the behaviour tests per validator.
 * This test guards that every event type resolves to a rule set and how validate() runs them.
 */
class PetitionEventRulesTest extends TestCase
{
    #[Test]
    #[DataProvider('petitionEventTypeProvider')]
    public function testEveryEventTypeResolvesToARuleSet(PetitionEventType $type): void
    {
        $this->assertContainsOnlyInstancesOf(ValidationRuleInterface::class, PetitionEventRules::for($type));
    }

    #[Test]
    public function testValidateStopsAtTheFirstFailingRule(): void
    {
        // The new event lies before the last event and is a duplicate: both the latest event rule and the
        // uniqueness rule that follows it would fail, but only the first failure is reported.
        $state = new DerivedState()->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::COMMITTEE_HEARING_DETERMINED,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now()->subMinute(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ]));

        $result = PetitionEventRules::validate(PetitionEventType::COMMITTEE_HEARING_DETERMINED, new PetitionEventData(
            type: PetitionEventType::COMMITTEE_HEARING_DETERMINED,
            date: CalendarDate::create('2025-01-12'),
            createdAt: CarbonImmutable::now(),
            duration: 42,
        ), $state);

        $this->assertSame(
            [
                'date' => __('term.validation.common.date_must_be_latest_event', [
                    'event' => PetitionEventType::COMMITTEE_HEARING_DETERMINED->label(),
                ]),
            ],
            $result->getErrors(),
        );
    }

    /**
     * @return array<string, array{PetitionEventType}>
     */
    public static function petitionEventTypeProvider(): array
    {
        $cases = [];
        foreach (PetitionEventType::cases() as $case) {
            $cases[$case->name] = [$case];
        }

        return $cases;
    }
}
