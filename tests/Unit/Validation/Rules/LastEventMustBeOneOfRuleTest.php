<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\Rules;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Validation\Rules\LastEventMustBeOneOfRule;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function __;
use function collect;

class LastEventMustBeOneOfRuleTest extends TestCase
{
    /**
     * Every event is a [type, date, created at] triple
     *
     * @return array<string, array{list<array{PetitionEventType, string, string}>, bool}>
     */
    public static function lastEventProvider(): array
    {
        return [
            'no events' => [
                [],
                false,
            ],
            'the last event is allowed' => [
                [
                    [PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-10', '2025-01-10'],
                    [PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2025-01-20', '2025-01-20'],
                ],
                true,
            ],
            'the last event is not allowed' => [
                [
                    [PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2025-01-10', '2025-01-10'],
                    [PetitionEventType::HEARING_DATE, '2025-01-20', '2025-01-20'],
                ],
                false,
            ],
            'the last created event counts, not the latest date' => [
                [
                    [PetitionEventType::HEARING_DATE, '2025-01-20', '2025-01-10'],
                    [PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2025-01-10', '2025-01-20'],
                ],
                true,
            ],
        ];
    }

    /**
     * @param list<array{PetitionEventType, string, string}> $events
     */
    #[Test]
    #[DataProvider('lastEventProvider')]
    public function testLastEventMustBeOneOf(array $events, bool $passes): void
    {
        $state = new DerivedState()->addEvents(collect($events)->map(
            static fn(array $event): PetitionEventData => new PetitionEventData(
                type: $event[0],
                date: CalendarDate::create($event[1]),
                createdAt: CarbonImmutable::parse($event[2]),
            ),
        ));
        $rule = new LastEventMustBeOneOfRule([PetitionEventType::LETTER_OF_SUSPENSION_SENT, PetitionEventType::ADJOURNMENT]);

        $result = $rule->validate(new PetitionEventData(
            type: PetitionEventType::SUSPENSION_END,
            date: CalendarDate::create('2025-01-25'),
            createdAt: CarbonImmutable::parse('2025-01-25'),
        ), $state);

        if ($passes) {
            $this->assertNull($result);

            return;
        }

        $this->assertSame(
            __('term.validation.common.last_event_must_be_one_of', [
                'events' => PetitionEventType::LETTER_OF_SUSPENSION_SENT->label() . ', ' . PetitionEventType::ADJOURNMENT->label(),
            ]),
            $result?->getErrors()['general'],
        );
    }
}
