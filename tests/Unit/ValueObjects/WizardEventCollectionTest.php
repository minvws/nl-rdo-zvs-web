<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\Enums\PetitionEventType;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class WizardEventCollectionTest extends TestCase
{
    #[Test]
    public function itCreatesEmptyCollection(): void
    {
        $collection = WizardEventCollection::make();

        $this->assertTrue($collection->isEmpty());
        $this->assertSame(0, $collection->count());
        $this->assertSame([], $collection->toArray());
    }

    #[Test]
    public function itAddsEventImmutably(): void
    {
        $collection = WizardEventCollection::make();
        $event = new PetitionEventData(
            type: PetitionEventType::RECEIPT_OF_OBJECTION,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
        );

        $newCollection = $collection->add($event);

        $this->assertTrue($collection->isEmpty());
        $this->assertFalse($newCollection->isEmpty());
        $this->assertSame(1, $newCollection->count());
        $this->assertEquals($event->type, $newCollection->last()->type);
        $this->assertEquals($event->date, $newCollection->last()->date);
        $this->assertEquals($event->duration, $newCollection->last()->duration);
        $this->assertNotNull($newCollection->last()->createdAt);
    }

    #[Test]
    public function itRemovesAnEventImmutably(): void
    {
        [$event1, $event2, $event3] = $this->threeEvents();
        $collection = WizardEventCollection::make()->add($event1)->add($event2)->add($event3);

        $newCollection = $collection->remove($event2->id);

        $this->assertSame(3, $collection->count());
        $this->assertSame([$event1, $event3], $newCollection->all()->all());
    }

    #[Test]
    public function itHandlesRemoveOfAnUnknownEvent(): void
    {
        [$event1] = $this->threeEvents();
        $collection = WizardEventCollection::make()->add($event1);

        $this->assertSame([$event1], $collection->remove(Uuid::uuid7())->all()->all());
    }

    #[Test]
    public function itFindsAnEventById(): void
    {
        [$event1, $event2] = $this->threeEvents();
        $collection = WizardEventCollection::make()->add($event1)->add($event2);

        $this->assertSame($event2, $collection->find(Uuid::fromString($event2->id->toString())));
        $this->assertNull($collection->remove($event2->id)->find($event2->id));
    }

    #[Test]
    public function itReturnsTheEventsBeforeAndAfterAnEvent(): void
    {
        [$event1, $event2, $event3] = $this->threeEvents();
        $collection = WizardEventCollection::make()->add($event1)->add($event2)->add($event3);

        $this->assertSame([], $collection->before($event1)->all()->all());
        $this->assertSame([$event1], $collection->before($event2)->all()->all());
        $this->assertSame([$event3], $collection->after($event2)->all()->all());
        $this->assertSame([], $collection->after($event3)->all()->all());
    }

    #[Test]
    public function itRejectsAnEventThatIsNotInTheCollection(): void
    {
        [$event1, $event2] = $this->threeEvents();

        $this->expectException(InvalidArgumentException::class);

        WizardEventCollection::make()->add($event1)->before($event2);
    }

    #[Test]
    public function itConvertsToArray(): void
    {
        $event1 = new PetitionEventData(
            type: PetitionEventType::RECEIPT_OF_OBJECTION,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
        );
        $event2 = new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create('2025-02-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
        );

        $collection = WizardEventCollection::make()
            ->add($event1)
            ->add($event2);

        $array = $collection->toArray();

        $this->assertCount(2, $array);
        $this->assertSame(PetitionEventType::RECEIPT_OF_OBJECTION->value, $array[0]['type']);
        $this->assertSame(PetitionEventType::PRIMARY_DECISION->value, $array[1]['type']);
    }

    #[Test]
    public function itPreservesEventOrder(): void
    {
        $collection = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ));

        $this->assertSame(2, $collection->count());
        $this->assertSame(PetitionEventType::PRIMARY_DECISION, $collection->all()->first()->type);
        $this->assertSame(PetitionEventType::RECEIPT_OF_OBJECTION, $collection->all()->last()->type);
    }

    #[Test]
    public function itReturnsLastEvent(): void
    {
        $collection = WizardEventCollection::make()
            ->add(new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ))
            ->add(new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ));

        $last = $collection->last();

        $this->assertNotNull($last);
        $this->assertSame(PetitionEventType::RECEIPT_OF_OBJECTION, $last->type);
    }

    #[Test]
    public function itReturnsNullForLastOnEmptyCollection(): void
    {
        $collection = WizardEventCollection::make();

        $this->assertNull($collection->last());
    }

    /**
     * @return array{PetitionEventData, PetitionEventData, PetitionEventData}
     */
    private function threeEvents(): array
    {
        return [
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-02-15'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
            new PetitionEventData(
                type: PetitionEventType::HEARING_DATE,
                date: CalendarDate::create('2025-03-15'),
                createdAt: CarbonImmutable::now(),
            ),
        ];
    }
}
