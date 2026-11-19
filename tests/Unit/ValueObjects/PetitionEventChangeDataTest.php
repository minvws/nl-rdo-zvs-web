<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\Enums\PetitionEventChange;
use App\Enums\PetitionEventType;
use App\ValueObjects\PetitionEventChangeData;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ValueError;

final class PetitionEventChangeDataTest extends TestCase
{
    #[Test]
    public function testNamedConstructorsSetTypeAndChange(): void
    {
        $unchanged = PetitionEventChangeData::createForUnchanged(PetitionEventType::PRIMARY_DECISION);
        $added = PetitionEventChangeData::createForAdded(PetitionEventType::PRIMARY_DECISION);
        $removed = PetitionEventChangeData::createForRemoved(PetitionEventType::PRIMARY_DECISION);

        $this->assertSame(PetitionEventType::PRIMARY_DECISION, $unchanged->type);
        $this->assertSame(PetitionEventChange::UNCHANGED, $unchanged->change);
        $this->assertSame(PetitionEventChange::ADDED, $added->change);
        $this->assertSame(PetitionEventChange::REMOVED, $removed->change);
    }

    #[Test]
    public function testIsAddedIsRemovedAndIsChanged(): void
    {
        $unchanged = PetitionEventChangeData::createForUnchanged(PetitionEventType::PRIMARY_DECISION);
        $added = PetitionEventChangeData::createForAdded(PetitionEventType::PRIMARY_DECISION);
        $removed = PetitionEventChangeData::createForRemoved(PetitionEventType::PRIMARY_DECISION);

        $this->assertFalse($unchanged->isAdded());
        $this->assertFalse($unchanged->isRemoved());
        $this->assertFalse($unchanged->isChanged());

        $this->assertTrue($added->isAdded());
        $this->assertFalse($added->isRemoved());
        $this->assertTrue($added->isChanged());

        $this->assertFalse($removed->isAdded());
        $this->assertTrue($removed->isRemoved());
        $this->assertTrue($removed->isChanged());
    }

    #[Test]
    public function testToArrayAndFromArrayRoundTrip(): void
    {
        $data = PetitionEventChangeData::createForRemoved(PetitionEventType::RECEIPT_OF_OBJECTION);

        $this->assertSame(
            ['type' => 'receipt_of_objection', 'change' => 'removed'],
            $data->toArray(),
        );
        $this->assertEquals($data, PetitionEventChangeData::fromArray($data->toArray()));
    }

    #[Test]
    public function testFromArrayThrowsOnUnknownChange(): void
    {
        $this->expectException(ValueError::class);

        PetitionEventChangeData::fromArray(['type' => 'primary_decision', 'change' => 'moved']);
    }
}
