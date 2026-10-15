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

class ComplaintReceiptValidatorTest extends TestCase
{
    #[Test]
    public function testItPassesWhenNoComplaintReceiptExists(): void
    {
        $state = new DerivedState();
        $state->addEvents(collect([]));

        $result = PetitionEventRules::validate(PetitionEventType::RECEIPT_OF_COMPLAINT, new PetitionEventData(
            type: PetitionEventType::RECEIPT_OF_COMPLAINT,
            date: CalendarDate::create('2025-01-01'),
            createdAt: CarbonImmutable::now(),
            duration: 42,
        ), $state);

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function testItFailsWhenComplaintReceiptAlreadyExists(): void
    {
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_COMPLAINT,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ]));

        $result = PetitionEventRules::validate(PetitionEventType::RECEIPT_OF_COMPLAINT, new PetitionEventData(
            type: PetitionEventType::RECEIPT_OF_COMPLAINT,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 42,
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertSame(
            [
                'general' => __('term.validation.common.only_one_event_allowed', [
                    'event' => PetitionEventType::RECEIPT_OF_COMPLAINT->label(),
                ]),
            ],
            $result->getErrors(),
        );
    }
}
