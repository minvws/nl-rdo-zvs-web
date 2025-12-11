<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\PublicHoliday;

use App\Actions\PublicHoliday\UpdatePublicHolidayAction;
use App\Models\PublicHoliday;
use Tests\Feature\FeatureTestCase;

class UpdatePublicHolidayActionTest extends FeatureTestCase
{
    public function testExecute(): void
    {
        $publicHoliday = PublicHoliday::factory()->create();
        $newName = $this->faker->word();
        $newDate = $this->faker->calendarDate();

        $updateData = [
            'name' => $newName,
            'date' => $newDate->format('Y-m-d'),
        ];

        $action = $this->app->make(UpdatePublicHolidayAction::class);
        $action->execute($publicHoliday, $updateData);

        $this->assertDatabaseHas(PublicHoliday::class, [
            'id' => $publicHoliday->id,
            'name' => $newName,
            'date' => $newDate,
        ]);
    }
}
