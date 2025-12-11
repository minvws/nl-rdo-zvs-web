<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\Enums\PetitionEventType;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
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
    public function itRemovesLastEventImmutably(): void
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

        $this->assertSame(2, $collection->count());

        $newCollection = $collection->removeLast();

        $this->assertSame(2, $collection->count());
        $this->assertSame(1, $newCollection->count());
        $this->assertEquals($event1->type, $newCollection->last()->type);
        $this->assertEquals($event1->date, $newCollection->last()->date);
        $this->assertEquals($event1->duration, $newCollection->last()->duration);
    }

    #[Test]
    public function itHandlesRemoveLastOnEmptyCollection(): void
    {
        $collection = WizardEventCollection::make();
        $newCollection = $collection->removeLast();

        $this->assertTrue($newCollection->isEmpty());
        $this->assertSame($collection, $newCollection);
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
}
