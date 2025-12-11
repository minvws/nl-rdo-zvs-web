<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Petition;

use App\Enums\TermType;
use App\Models\Petition;
use App\Models\PetitionDeliverable;
use App\Models\PetitionDraftTerm;
use App\Models\PetitionTerm;
use App\Services\Petition\PetitionDeadlineService;
use Tests\Feature\FeatureTestCase;

class PetitionDeadlineServiceTest extends FeatureTestCase
{
    private PetitionDeadlineService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(PetitionDeadlineService::class);
    }

    public function testCalculateDeadlineReturnsNullWhenPetitionHasDraftTerm(): void
    {
        $petition = Petition::factory()->create();
        PetitionDraftTerm::factory()->create(['petition_id' => $petition->id]);

        $result = $this->service->calculateDeadline($petition);

        $this->assertNull($result);
    }

    public function testCalculateDeadlineReturnsNullWhenPetitionHasDraftTermEvenWithDeliverables(): void
    {
        $petition = Petition::factory()->create();
        PetitionDraftTerm::factory()->create(['petition_id' => $petition->id]);
        PetitionDeliverable::factory()->create(['petition_id' => $petition->id]);

        $result = $this->service->calculateDeadline($petition);

        $this->assertNull($result);
    }

    public function testCalculateDeadlineReturnsNullWhenPetitionHasDraftTermEvenWithTerms(): void
    {
        $petition = Petition::factory()->create();
        PetitionDraftTerm::factory()->create(['petition_id' => $petition->id]);
        PetitionTerm::factory()->create(['petition_id' => $petition->id]);

        $result = $this->service->calculateDeadline($petition);

        $this->assertNull($result);
    }

    public function testCalculateDeadlineReturnsDeliverablesDeadlineWhenHasDeliverables(): void
    {
        $deliverableDeadline = $this->faker->calendarDate();
        $petition = Petition::factory()->create();
        PetitionDeliverable::factory()->create([
            'petition_id' => $petition->id,
            'deadline_at' => $deliverableDeadline,
        ]);

        $result = $this->service->calculateDeadline($petition);

        $this->assertEquals($deliverableDeadline, $result);
    }

    public function testCalculateDeadlineReturnsLatestDeliverablesDeadlineWhenHasMultipleDeliverables(): void
    {
        $earlierDeadline = $this->faker->calendarDate();
        $laterDeadline = $earlierDeadline->addDays(10);
        $petition = Petition::factory()->create();

        PetitionDeliverable::factory()->create([
            'petition_id' => $petition->id,
            'deadline_at' => $earlierDeadline,
        ]);
        PetitionDeliverable::factory()->create([
            'petition_id' => $petition->id,
            'deadline_at' => $laterDeadline,
        ]);

        $result = $this->service->calculateDeadline($petition);

        $this->assertEquals($laterDeadline, $result);
    }

    public function testCalculateDeadlineReturnsTermsDeadlineWhenHasTermsButNoDeliverables(): void
    {
        $petition = Petition::factory()->create();

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => 'first', // TermType that supports deadlines
            'start_date' => $this->faker->calendarDate(),
            'duration_in_days' => 7,
        ]);

        $result = $this->service->calculateDeadline($petition);

        $this->assertNotNull($result);
    }

    public function testCalculateDeadlineReturnsDateOfEntryWhenTermsDeadlineIsNull(): void
    {
        $dateOfEntry = $this->faker->calendarDate();
        $petition = Petition::factory()->create(['date_of_entry' => $dateOfEntry]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::SUSPENSION->value,
            'start_date' => $this->faker->calendarDate(),
            'duration_in_days' => 7,
        ]);

        $result = $this->service->calculateDeadline($petition);

        $this->assertEquals($dateOfEntry, $result);
    }

    public function testCalculateDeadlineReturnsDateOfEntryWhenTermsIsEmpty(): void
    {
        $dateOfEntry = $this->faker->calendarDate();
        $petition = Petition::factory()->create(['date_of_entry' => $dateOfEntry]);

        $result = $this->service->calculateDeadline($petition);

        $this->assertEquals($dateOfEntry, $result);
    }

    public function testCalculateDeadlineReturnsDateOfEntryWhenNoTermsExist(): void
    {
        $petition = Petition::factory()->create();

        $result = $this->service->calculateDeadline($petition);

        $this->assertEquals($petition->date_of_entry, $result);
    }

    public function testCalculateDeadlineWithDraftTerm(): void
    {
        $petition = Petition::factory()->create();
        PetitionDraftTerm::factory()->recycle($petition)->create();
        $petition->refresh();

        $result = $this->service->calculateDeadline($petition);

        $this->assertNull($result);
    }
}
