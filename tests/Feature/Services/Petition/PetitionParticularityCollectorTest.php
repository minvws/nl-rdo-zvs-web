<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Petition;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Models\PetitionType;
use App\Services\Petition\PetitionParticularityCollector;
use App\ValueObjects\CalendarDate;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class PetitionParticularityCollectorTest extends FeatureTestCase
{
    public function testLabelsReferencedPetitionFromOtherDepartment(): void
    {
        $label = $this->faker->word();

        $petitionType = PetitionType::factory()->create([
            'particularity_label' => $label,
        ]);
        $dep1 = Department::factory()->create();
        $dep2 = Department::factory()->create();

        $petition1 = Petition::factory()->recycle($dep1)->create();
        $petition2 = Petition::factory()->recycle($dep2)->recycle($petitionType)->create();

        DB::table('petition_petition')->insert([
            'petition_id' => $petition1->id,
            'related_petition_id' => $petition2->id,
        ]);

        $collector = $this->app->make(PetitionParticularityCollector::class);

        $labels = $collector->collectParticularities($petition1);
        $this->assertEquals([$label], $labels);
    }

    public function testLabelsWithActualSuspension(): void
    {
        $department = Department::factory()->create();

        $petition = Petition::factory()->recycle($department)->create();

        $petition->petitionTerms()->create([
            'id' => $this->faker->uuid(),
            'type' => TermType::SUSPENSION,
            'start_date' => CalendarDate::today(),
            'duration_in_days' => $this->faker->numberBetween(1, 100),
            'penalty_amount_in_euros' => $this->faker->numberBetween(1, 100),
        ]);

        $collector = $this->app->make(PetitionParticularityCollector::class);

        $labels = $collector->collectParticularities($petition);

        $this->assertEquals(['Opsch'], $labels);
    }

    public function testLabelsWithActualAdjournment(): void
    {
        $department = Department::factory()->create();

        $petition = Petition::factory()->recycle($department)->create();

        $petition->petitionTerms()->create([
            'id' => $this->faker->uuid(),
            'type' => TermType::SPECIFIED_ADJOURNMENT,
            'start_date' => CalendarDate::today(),
            'duration_in_days' => $this->faker->numberBetween(1, 100),
            'penalty_amount_in_euros' => $this->faker->numberBetween(1, 100),
        ]);

        $collector = $this->app->make(PetitionParticularityCollector::class);

        $labels = $collector->collectParticularities($petition);

        $this->assertEquals(['Aanh'], $labels);
    }

    public function testLabelsWithFutureDraftTerm(): void
    {
        $department = Department::factory()->create();

        $petition = Petition::factory()->recycle($department)->create();

        $startDate = CalendarDate::today()->addDay();

        $petition->draftTerm()->create([
            'id' => $this->faker->uuid(),
            'start_date' => $startDate,
            'days_after_event' => $this->faker->numberBetween(0, 365),
            'days_after_date_withdrawal' => $this->faker->optional()->numberBetween(0, 365),
        ]);

        $petition->refresh();

        $collector = $this->app->make(PetitionParticularityCollector::class);

        $labels = $collector->collectParticularities($petition);

        $this->assertEquals(['Aanh'], $labels);
    }

    public function testLabelsWithNoticeOfDefault(): void
    {
        $department = Department::factory()->create();

        $petition = Petition::factory()->recycle($department)->create();

        $petition->petitionTerms()->create([
            'id' => $this->faker->uuid(),
            'start_date' => CalendarDate::today(),
            'type' => TermType::NOTICE_OF_DEFAULT,
            'duration_in_days' => $this->faker->numberBetween(1, 100),
            'penalty_amount_in_euros' => $this->faker->numberBetween(1, 100),
        ]);

        $collector = $this->app->make(PetitionParticularityCollector::class);

        $labels = $collector->collectParticularities($petition);

        $this->assertEquals(['IGS'], $labels);
    }

    #[Test]
    public function testLabelsWithTev2NoticeOfDefaultReceived(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        PetitionEvent::factory()->recycle($petition)->withType(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED)->create();

        $petition->refresh();

        $collector = $this->app->make(PetitionParticularityCollector::class);
        $labels = $collector->collectParticularities($petition);

        $this->assertEquals(['IGS'], $labels);
    }

    #[Test]
    public function testLabelsWithTev2NoticeOfDefaultWithdrawn(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        PetitionEvent::factory()->recycle($petition)->withType(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED)->create();
        PetitionEvent::factory()->recycle($petition)->withType(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN)->create();

        $petition->refresh();

        $collector = $this->app->make(PetitionParticularityCollector::class);
        $labels = $collector->collectParticularities($petition);

        $this->assertEquals([], $labels);
    }

    #[Test]
    public function testLabelsWithTev2MultipleReceivedOneWithdrawn(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        PetitionEvent::factory()->recycle($petition)->withType(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED)->count(2)->create();
        PetitionEvent::factory()->recycle($petition)->withType(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN)->create();

        $petition->refresh();

        $collector = $this->app->make(PetitionParticularityCollector::class);
        $labels = $collector->collectParticularities($petition);

        $this->assertEquals(['IGS'], $labels);
    }
}
