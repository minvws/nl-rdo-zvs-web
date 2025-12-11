<?php

declare(strict_types=1);

namespace Tests\Feature\Factories\View\Petition;

use App\Enums\CustomDateLabel;
use App\Factories\View\Petition\PetitionCustomDatesViewFactory;
use App\Models\Petition;
use App\Models\PetitionTypeCustomDateLabel;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Tests\Feature\FeatureTestCase;

class PetitionCustomDatesViewFactoryTest extends FeatureTestCase
{
    public function testPetitionCustomDatesViewFactory(): void
    {
        $petition = Petition::factory()->create();

        // Create custom dates using the new relationship
        $petition->customDates()->create([
            'date_label' => CustomDateLabel::DATE_RULING,
            'date' => CalendarDate::createFromFormat('Y-m-d', '2021-01-01'),
        ]);
        $petition->customDates()->create([
            'date_label' => CustomDateLabel::DATE_WITHDRAWN,
            'date' => CalendarDate::createFromFormat('Y-m-d', '2021-01-02'),
        ]);

        PetitionTypeCustomDateLabel::factory()
            ->count(3)
            ->state(new Sequence(
                ['date_label' => CustomDateLabel::DATE_RULING],
                ['date_label' => CustomDateLabel::DATE_WITHDRAWN],
                ['date_label' => CustomDateLabel::DATE_APPOINTMENT_WITH_APPLICANT],
            ))
            ->create([
                'petition_type_id' => $petition->petition_type_id,
            ]);

        $petitionCustomDatesViewFactory = $this->app->get(PetitionCustomDatesViewFactory::class);
        $customDatesCollection = $petitionCustomDatesViewFactory->build($petition);

        $this->assertCount(3, $customDatesCollection);
        $this->assertEquals(
            '2021-01-01',
            $customDatesCollection->firstWhere('date_label', CustomDateLabel::DATE_RULING)->date->format('Y-m-d'),
        );
        $this->assertEquals(
            '2021-01-02',
            $customDatesCollection->firstWhere('date_label', CustomDateLabel::DATE_WITHDRAWN)->date->format('Y-m-d'),
        );
        $this->assertNull($customDatesCollection->firstWhere('date_label', CustomDateLabel::DATE_APPOINTMENT_WITH_APPLICANT)->date);
    }
}
