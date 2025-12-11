<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\PublicHoliday;

use App\Actions\PublicHoliday\CreatePublicHolidayAction;
use App\Models\PublicHoliday;
use Tests\Feature\FeatureTestCase;

class CreatePublicHolidayActionTest extends FeatureTestCase
{
    public function testExecute(): void
    {
        $name = $this->faker->word();
        $date = $this->faker->calendarDate();

        $publicHolidayData = [
            'name' => $name,
            'date' => $date->format('Y-m-d'),
        ];

        $action = $this->app->make(CreatePublicHolidayAction::class);
        $action->execute($publicHolidayData);

        $this->assertDatabaseHas(PublicHoliday::class, [
            'name' => $name,
            'date' => $date,
        ]);
    }
}
