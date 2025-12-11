<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\Enums\PetitionEventType;
use App\Enums\ResultType;
use App\Enums\SuspensionType;
use App\Exceptions\InvalidPetitionEventData;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PenaltyData;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PetitionEventDataTest extends TestCase
{
    #[Test]
    public function itCreatesValidEventWithoutPenalties(): void
    {
        $event = new PetitionEventData(
            type: PetitionEventType::RECEIPT_OF_OBJECTION,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
        );

        $this->assertSame(PetitionEventType::RECEIPT_OF_OBJECTION, $event->type);
        $this->assertInstanceOf(CalendarDate::class, $event->date);
        $this->assertSame('2025-01-15', $event->date->format('Y-m-d'));
        $this->assertSame(30, $event->duration);
        $this->assertSame([], $event->penalties);
    }

    #[Test]
    public function itCreatesValidEventWithPenalties(): void
    {
        $penalties = [
            new PenaltyData(amount: 500, duration: 10),
            new PenaltyData(amount: 750, duration: 15),
        ];

        $event = new PetitionEventData(
            type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
            penalties: $penalties,
        );

        $this->assertSame(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, $event->type);
        $this->assertInstanceOf(CalendarDate::class, $event->date);
        $this->assertSame('2025-01-15', $event->date->format('Y-m-d'));
        $this->assertSame(30, $event->duration);
        $this->assertCount(2, $event->penalties);
        $this->assertSame(500, $event->penalties[0]->amount);
        $this->assertSame(10, $event->penalties[0]->duration);
    }

    #[Test]
    public function itCreatesEventWithNullDuration(): void
    {
        $event = new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: null,
        );

        $this->assertInstanceOf(CalendarDate::class, $event->date);
        $this->assertNull($event->duration);
    }

    #[Test]
    public function itCreatesEventWithoutDurationParameter(): void
    {
        $event = new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
        );

        $this->assertInstanceOf(CalendarDate::class, $event->date);
        $this->assertNull($event->duration);
        $this->assertSame([], $event->penalties);
    }

    #[Test]
    public function itConvertsToArrayWithoutPenalties(): void
    {
        $event = new PetitionEventData(
            type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            suspensionType: SuspensionType::SUSPENSION,
            duration: 30,
        );

        $array = $event->toArray();

        $this->assertSame(PetitionEventType::LETTER_OF_SUSPENSION_SENT->value, $array['type']);
        $this->assertSame('2025-01-15', $array['date']);
        $this->assertSame(30, $array['duration']);
        $this->assertInstanceOf(CarbonImmutable::class, $array['created_at']);
        $this->assertSame(SuspensionType::SUSPENSION->value, $array['suspension_type']);
    }

    #[Test]
    public function itConvertsToArrayWithPenalties(): void
    {
        $penalties = [
            new PenaltyData(amount: 500, duration: 10),
            new PenaltyData(amount: 750, duration: 15),
        ];

        $event = new PetitionEventData(
            type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
            penalties: $penalties,
        );

        $array = $event->toArray();

        $this->assertSame(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->value, $array['type']);
        $this->assertSame('2025-01-15', $array['date']);
        $this->assertSame(30, $array['duration']);
        $this->assertInstanceOf(CarbonImmutable::class, $array['created_at']);
        $this->assertSame([
            ['amount' => 500, 'duration' => 10],
            ['amount' => 750, 'duration' => 15],
        ], $array['penalties']);
    }

    #[Test]
    public function itConvertsToArrayWithNullDuration(): void
    {
        $event = new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: null,
        );

        $array = $event->toArray();

        $this->assertSame(PetitionEventType::PRIMARY_DECISION->value, $array['type']);
        $this->assertSame('2025-01-15', $array['date']);
        $this->assertNull($array['duration']);
        $this->assertInstanceOf(CarbonImmutable::class, $array['created_at']);
    }

    #[Test]
    public function itConvertsToArrayWithEmptyPenalties(): void
    {
        $event = new PetitionEventData(
            type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
            penalties: [],
        );

        $this->assertSame([], $event->penalties);
        $this->assertArrayNotHasKey('penalties', $event->toArray());
    }

    #[Test]
    public function itIsImmutable(): void
    {
        $penalties = [
            new PenaltyData(amount: 500, duration: 10),
        ];

        $event = new PetitionEventData(
            type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
            penalties: $penalties,
        );

        $this->assertSame(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, $event->type);
        $this->assertInstanceOf(CalendarDate::class, $event->date);
        $this->assertSame('2025-01-15', $event->date->format('Y-m-d'));
        $this->assertSame(30, $event->duration);
        $this->assertCount(1, $event->penalties);
    }

    #[Test]
    public function itHandlesAllEventTypes(): void
    {
        foreach (PetitionEventType::cases() as $type) {
            $event = new PetitionEventData(
                type: $type,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            );

            $this->assertSame($type, $event->type);
            $this->assertInstanceOf(CalendarDate::class, $event->date);
        }
    }

    #[Test]
    public function itThrowsExceptionWhenSuspensionTypeProvidedForNonSuspensionEvent(): void
    {
        $this->expectException(InvalidPetitionEventData::class);
        $this->expectExceptionMessage('Event type "primary_decision" does not support suspension type');

        new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
            suspensionType: SuspensionType::SUSPENSION,
        );
    }

    #[Test]
    public function itAllowsSuspensionTypeForSuspensionEvents(): void
    {
        $event = new PetitionEventData(
            type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
            suspensionType: SuspensionType::SUSPENSION,
        );

        $this->assertSame(SuspensionType::SUSPENSION, $event->suspensionType);
    }

    #[Test]
    public function itThrowsExceptionWhenResultTypeProvidedForNonFinalResultEvent(): void
    {
        $this->expectException(InvalidPetitionEventData::class);
        $this->expectExceptionMessage('Event type "primary_decision" does not support result type');

        new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
            resultType: ResultType::FINAL_DECISION,
        );
    }

    #[Test]
    public function itAllowsResultTypeForFinalResultEvents(): void
    {
        $event = new PetitionEventData(
            type: PetitionEventType::FINAL_RESULT,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            resultType: ResultType::FINAL_DECISION,
        );

        $this->assertSame(ResultType::FINAL_DECISION, $event->resultType);
    }

    #[Test]
    public function itConvertsToArrayWithResultType(): void
    {
        $event = new PetitionEventData(
            type: PetitionEventType::FINAL_RESULT,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            resultType: ResultType::WITHDRAWN,
        );

        $array = $event->toArray();

        $this->assertSame(PetitionEventType::FINAL_RESULT->value, $array['type']);
        $this->assertSame('2025-01-15', $array['date']);
        $this->assertInstanceOf(CarbonImmutable::class, $array['created_at']);
        $this->assertSame(ResultType::WITHDRAWN->value, $array['result_type']);
    }

    #[Test]
    public function itOmitsResultTypeFromArrayWhenNull(): void
    {
        $event = new PetitionEventData(
            type: PetitionEventType::FINAL_RESULT,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
        );

        $array = $event->toArray();

        $this->assertArrayNotHasKey('result_type', $array);
    }

    #[Test]
    public function itConvertsToArrayWithAllResultTypes(): void
    {
        foreach (ResultType::cases() as $resultType) {
            $event = new PetitionEventData(
                type: PetitionEventType::FINAL_RESULT,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                resultType: $resultType,
            );

            $array = $event->toArray();

            $this->assertSame($resultType->value, $array['result_type']);
        }
    }
}
