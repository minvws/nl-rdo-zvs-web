<?php

declare(strict_types=1);

namespace Tests\Feature\Collections;

use App\Enums\CustomDateLabel;
use App\Models\Petition;
use Tests\Feature\FeatureTestCase;

class PetitionCustomDateCollectionTest extends FeatureTestCase
{
    public function testCollectionGivesMaxDateIfDateIsAffectingCloseDate(): void
    {
        $date = $this->faker->calendarDate();
        $petition = Petition::factory()->create();

        // Create custom dates using the new relationship
        $petition->customDates()->create([
            'date_label' => CustomDateLabel::DATE_RULING,
            'date' => $date->subDays(10),
        ]);
        $petition->customDates()->create([
            'date_label' => CustomDateLabel::DATE_DECISION_ON_APPEAL,
            'date' => $date,
        ]);
        $petition->customDates()->create([
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
            'date' => $date->addDays(10),
        ]);

        $this->assertEquals($date->format('Y-m-d'), $petition->customDates->getMaxDateForDateOfClose()->format('Y-m-d'));
    }

    public function testCollectionGivesNullIfDateIsNotAffectingCloseDate(): void
    {
        $date = $this->faker->calendarDate();
        $petition = Petition::factory()->create();

        // Create custom date using the new relationship (DATE_PUBLIC_HEARING doesn't affect close date)
        $petition->customDates()->create([
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
            'date' => $date->addDays(10),
        ]);

        $this->assertNull($petition->customDates->getMaxDateForDateOfClose());
    }
}
