<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition;

use App\Actions\Petition\PetitionTermsUpdateAction;
use App\Enums\TermType;
use App\Models\Petition;
use App\Models\PetitionDraftTerm;
use App\Models\PetitionTerm;
use App\ValueObjects\CalendarDate;
use Tests\Feature\FeatureTestCase;

class PetitionTermsUpdateActionTest extends FeatureTestCase
{
    public function testPetitionUpdatesDeadlineAtWithoutTerms(): void
    {
        $dateOfEntry = $this->faker->calendarDate();

        $petition = Petition::factory()->create([
            'date_of_entry' => $dateOfEntry,
            'deadline_at' => $this->faker->calendarDate(),
        ]);

        $petitionTermsUpdateAction = $this->getPetitionTermsUpdateAction();
        $petitionTermsUpdateAction->execute($petition);

        $this->assertTrue($petition->deadline_at->equals($dateOfEntry));
    }

    public function testPetitionUpdatesDeadlineAtWithFirstTerm(): void
    {
        $workday = CalendarDate::create('01-01-2000');

        $petition = Petition::factory()->create([
            'date_of_entry' => $workday,
            'deadline_at' => $this->faker->calendarDate(),
        ]);
        PetitionTerm::factory()
            ->recycle($petition)
            ->create([
                'start_date' => $workday,
                'type' => TermType::FIRST,
                'duration_in_days' => 7,
            ]);

        $petitionTermsUpdateAction = $this->getPetitionTermsUpdateAction();
        $petitionTermsUpdateAction->execute($petition);

        $this->assertTrue($petition->deadline_at->equals($workday->addDays(6)));
    }

    public function testPetitionUpdatesDeadlineAtToNullWhenDraftTermExists(): void
    {
        $dateOfEntry = $this->faker->calendarDate();

        $petition = Petition::factory()->create([
            'date_of_entry' => $dateOfEntry,
            'deadline_at' => $this->faker->calendarDate(),
        ]);

        PetitionTerm::factory()
            ->recycle($petition)
            ->create([
                'start_date' => $dateOfEntry->addDay(),
                'type' => TermType::FIRST,
                'duration_in_days' => 7,
            ]);

        PetitionDraftTerm::factory()->create([
            'petition_id' => $petition->id,
        ]);

        $petitionTermsUpdateAction = $this->getPetitionTermsUpdateAction();
        $petitionTermsUpdateAction->execute($petition);

        $this->assertNull($petition->deadline_at);
    }

    public function testRecalculationLeavesTheDeadlineOverrideAlone(): void
    {
        $dateOfEntry = CalendarDate::create('01-01-2026');
        $override = CalendarDate::create('30-06-2026');

        $petition = Petition::factory()->create([
            'date_of_entry' => $dateOfEntry,
            'deadline_at' => $this->faker->calendarDate(),
            'deadline_override_at' => $override,
            'deadline_override_reason' => 'Afspraak met verzoeker',
        ]);

        $this->getPetitionTermsUpdateAction()->execute($petition);

        $this->assertTrue($petition->deadline_at->equals($dateOfEntry));
        $this->assertTrue($petition->deadline_override_at->equals($override));
        $this->assertSame('Afspraak met verzoeker', $petition->deadline_override_reason);
    }

    private function getPetitionTermsUpdateAction(): PetitionTermsUpdateAction
    {
        return $this->app->get(PetitionTermsUpdateAction::class);
    }
}
