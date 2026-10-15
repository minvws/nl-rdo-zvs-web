<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\PetitionEvent;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Validation\PetitionEvent\PetitionEventRules;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function __;
use function collect;

class ReferralValidatorTest extends TestCase
{
    #[Test]
    public function testReferralFailsWhileAnEarlierReferralHasNoAnswer(): void
    {
        $state = $this->createStateWith([
            [PetitionEventType::REFERRED_TO_DEPARTMENT, '2025-01-05'],
        ]);

        $result = PetitionEventRules::validate(PetitionEventType::REFERRED_TO_DEPARTMENT, new PetitionEventData(
            type: PetitionEventType::REFERRED_TO_DEPARTMENT,
            date: CalendarDate::create('2025-01-10'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertSame(
            __('term.validation.common.event_requires_closing_event', [
                'event' => PetitionEventType::REFERRED_TO_DEPARTMENT->label(),
                'required_event' => PetitionEventType::ANSWER_RECEIVED->label(),
            ]),
            $result->getErrors()['general'],
        );
    }

    #[Test]
    public function testAnswerFailsWhenEveryReferralAlreadyHasAnAnswer(): void
    {
        $state = $this->createStateWith([
            [PetitionEventType::REFERRED_TO_DEPARTMENT, '2025-01-05'],
            [PetitionEventType::ANSWER_RECEIVED, '2025-01-08'],
        ]);

        $result = PetitionEventRules::validate(PetitionEventType::ANSWER_RECEIVED, new PetitionEventData(
            type: PetitionEventType::ANSWER_RECEIVED,
            date: CalendarDate::create('2025-01-10'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertSame(
            __('term.validation.common.event_requires_open_dependency', [
                'event' => PetitionEventType::ANSWER_RECEIVED->label(),
                'required_event' => PetitionEventType::REFERRED_TO_DEPARTMENT->label(),
            ]),
            $result->getErrors()['general'],
        );
    }

    /**
     * @param array<array{PetitionEventType, string}> $followingEvents
     */
    private function createStateWith(array $followingEvents): DerivedState
    {
        $events = [
            new PetitionEventData(
                type: PetitionEventType::PETITION_RECEIVED,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 28,
            ),
        ];

        foreach ($followingEvents as [$type, $date]) {
            $events[] = new PetitionEventData(
                type: $type,
                date: CalendarDate::create($date),
                createdAt: CarbonImmutable::now(),
            );
        }

        $state = new DerivedState();
        $state->addEvents(collect($events));

        return $state;
    }
}
