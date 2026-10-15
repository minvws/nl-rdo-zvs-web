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

class PetitionReceivedValidatorTest extends TestCase
{
    #[Test]
    public function testItPassesWhenNoPetitionReceivedExists(): void
    {
        $state = new DerivedState();
        $state->addEvents(collect([]));

        $result = PetitionEventRules::validate(PetitionEventType::PETITION_RECEIVED, new PetitionEventData(
            type: PetitionEventType::PETITION_RECEIVED,
            date: CalendarDate::create('2025-01-01'),
            createdAt: CarbonImmutable::now(),
            duration: 42,
        ), $state);

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function testItFailsWhenPetitionReceivedAlreadyExists(): void
    {
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::PETITION_RECEIVED,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ]));

        $result = PetitionEventRules::validate(PetitionEventType::PETITION_RECEIVED, new PetitionEventData(
            type: PetitionEventType::PETITION_RECEIVED,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 42,
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertSame(
            [
                'general' => __('term.validation.common.only_one_event_allowed', [
                    'event' => PetitionEventType::PETITION_RECEIVED->label(),
                ]),
            ],
            $result->getErrors(),
        );
    }
}
