<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Database\Casts;

use App\Models\Decision;
use App\Models\Petition;
use Tests\Feature\FeatureTestCase;

class CalendarDateCastTest extends FeatureTestCase
{
    public function testGet(): void
    {
        $calendarDate = $this->faker->calendarDate();

        $petition = Petition::factory()
            ->create([
                'deadline_at' => $calendarDate,
            ]);

        $this->assertEquals($calendarDate, $petition->deadline_at);
    }

    public function testGetWhenNull(): void
    {
        $decision = Decision::factory()
            ->create([
                'date' => null,
            ]);

        $this->assertNull($decision->date);
    }

    public function testSet(): void
    {
        $calendarDate = $this->faker->calendarDate();

        $petition = Petition::factory()
            ->create([
                'deadline_at' => $calendarDate,
            ]);

        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'deadline_at' => $calendarDate,
        ]);
    }

    public function testSetWhenNull(): void
    {
        Decision::factory()
            ->create([
                'date' => null,
            ]);

        $this->assertDatabaseHas(Decision::class, [
            'date' => null,
        ]);
    }
}
