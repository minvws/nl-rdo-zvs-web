<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PeriodGenerators;

use App\Enums\PetitionEventType;
use App\Services\PeriodGenerators\EventsGenerator;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ValueError;

use function collect;

class EventsGeneratorTest extends TestCase
{
    #[Test]
    public function testItAddsPetitionEventTypeToCalendarDay(): void
    {
        $calendar = new EventCalendar();
        $generator = new EventsGenerator();

        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);

        $generator->generate($events, $calendar);

        $day = $calendar->findDay(CalendarDate::create('2025-01-15'));

        $this->assertNotNull($day);
        $this->assertCount(1, $day->petitionEvents);
        $this->assertSame(PetitionEventType::PRIMARY_DECISION, $day->petitionEvents[0]->type);
    }

    #[Test]
    public function testItAddsMultiplePetitionEventTypesToSameDay(): void
    {
        $calendar = new EventCalendar();
        $generator = new EventsGenerator();

        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);

        $generator->generate($events, $calendar);

        $day = $calendar->findDay(CalendarDate::create('2025-01-15'));

        $this->assertNotNull($day);
        $this->assertCount(2, $day->petitionEvents);
        $this->assertSame(PetitionEventType::PRIMARY_DECISION, $day->petitionEvents[0]->type);
        $this->assertSame(PetitionEventType::RECEIPT_OF_OBJECTION, $day->petitionEvents[1]->type);
    }

    #[Test]
    public function testItSortsPetitionEventTypesByCreatedAt(): void
    {
        $calendar = new EventCalendar();
        $generator = new EventsGenerator();

        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now()->addSeconds(1),
            ),
            new PetitionEventData(
                type: PetitionEventType::MEETING_SCHEDULED,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now()->addSeconds(3),
            ),
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now()->addSeconds(2),
            ),
        ]);

        $generator->generate($events, $calendar);

        $day = $calendar->findDay(CalendarDate::create('2025-01-15'));

        $this->assertNotNull($day);
        $this->assertCount(3, $day->petitionEvents);
        $this->assertSame(PetitionEventType::MEETING_SCHEDULED, $day->petitionEvents[2]->type);
        $this->assertSame(PetitionEventType::PRIMARY_DECISION, $day->petitionEvents[1]->type);
        $this->assertSame(PetitionEventType::RECEIPT_OF_OBJECTION, $day->petitionEvents[0]->type);
    }

    #[Test]
    public function testItDoesNotAddNullPetitionEventTypes(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('is not a valid backing value for enum');

        PetitionEventType::from('invalid_type');
    }

    #[Test]
    public function testItPreservesExistingPetitionEventTypesWhenAddingNewOnes(): void
    {
        $calendar = new EventCalendar();
        $generator = new EventsGenerator();

        $existingEvent = new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
        );

        $calendar->upsertDay(CalendarDate::create('2025-01-15'), [
            'petitionEvents' => [$existingEvent],
        ]);

        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);

        $generator->generate($events, $calendar);

        $day = $calendar->findDay(CalendarDate::create('2025-01-15'));

        $this->assertNotNull($day);
        $this->assertCount(2, $day->petitionEvents);
        $this->assertSame(PetitionEventType::PRIMARY_DECISION, $day->petitionEvents[0]->type);
        $this->assertSame(PetitionEventType::RECEIPT_OF_OBJECTION, $day->petitionEvents[1]->type);
    }
}
