<?php

declare(strict_types=1);

namespace Tests\Feature\Factories;

use App\Enums\PetitionEventType;
use App\Factories\PetitionEventDataFactory;
use App\Models\PetitionEvent;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class PetitionEventDataFactoryTest extends FeatureTestCase
{
    #[Test]
    public function mapsPetitionEventModelToPetitionEventData(): void
    {
        $event = new PetitionEvent();
        $event->type = PetitionEventType::cases()[0];
        $event->date = CarbonImmutable::now()->toDateString();
        $event->created_at = CarbonImmutable::now()->subDay();
        $event->duration = 15;
        $event->penalties = [
            ['amount' => 2, 'duration' => 10],
            ['amount' => 4, 'duration' => 20],
        ];
        $event->suspension_type = null;
        $event->result_type = null;

        $data = PetitionEventDataFactory::fromModel($event);

        $this->assertInstanceOf(PetitionEventData::class, $data);

        $arr = $data->toArray();

        $this->assertSame([
            ['amount' => 2, 'duration' => 10],
            ['amount' => 4, 'duration' => 20],
        ], $arr['penalties']);
        $this->assertInstanceOf(CarbonImmutable::class, $arr['created_at']);
    }

    #[Test]
    public function mapsNullPenaltiesToEmptyArray(): void
    {
        $event = new PetitionEvent();
        $event->type = PetitionEventType::cases()[0];
        $event->date = CarbonImmutable::now()->toDateString();
        $event->created_at = CarbonImmutable::now();
        $event->duration = null;
        $event->penalties = null;

        $data = PetitionEventDataFactory::fromModel($event);

        $arr = $data->toArray();

        $this->assertSame([], $arr['penalties'] ?? []);
        $this->assertInstanceOf(CarbonImmutable::class, $arr['created_at']);
    }
}
