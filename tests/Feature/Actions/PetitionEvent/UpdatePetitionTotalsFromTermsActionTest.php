<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\PetitionEvent;

use App\Actions\PetitionEvent\UpdatePetitionTotalsFromTermsAction;
use App\Enums\TermType;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Models\PetitionTerm;
use App\ValueObjects\CalendarDate;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class UpdatePetitionTotalsFromTermsActionTest extends FeatureTestCase
{
    private UpdatePetitionTotalsFromTermsAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(UpdatePetitionTotalsFromTermsAction::class);
    }

    #[Test]
    public function testUpdatesDeadlineAndTotalDaysSuspendedFromTerms(): void
    {
        $petition = Petition::factory()->create();

        $suspensionDays1 = 10;
        $suspensionDays2 = 15;

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::FIRST,
            'start_date' => CalendarDate::create('2025-01-01'),
            'duration_in_days' => 10,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::SUSPENSION,
            'start_date' => CalendarDate::create('2025-01-20'),
            'duration_in_days' => $suspensionDays1,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::SPECIFIED_ADJOURNMENT,
            'start_date' => CalendarDate::create('2025-02-01'),
            'duration_in_days' => $suspensionDays2,
        ]);

        $this->action->execute($petition);
        $petition->refresh();

        $this->assertEquals($suspensionDays1 + $suspensionDays2, $petition->total_days_suspended);
        $this->assertNotNull($petition->deadline_at);
    }

    #[Test]
    public function testCalculatesLegacyPenaltyFieldsFromPenaltyTerms(): void
    {
        $petition = Petition::factory()->create();
        $today = CalendarDate::today();

        $penaltyAmount = 100;
        $durationDays = 5;
        $totalPenalty = $penaltyAmount * $durationDays;

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::PENALTY,
            'start_date' => $today,
            'duration_in_days' => $durationDays,
            'penalty_amount_in_euros' => $penaltyAmount,
        ]);

        $this->action->execute($petition);
        $petition->refresh();

        $this->assertEquals($penaltyAmount, $petition->legacy_term_penalty_today);
        $this->assertEquals($totalPenalty, $petition->legacy_term_penalty_maximum);
        $this->assertEquals($penaltyAmount, $petition->legacy_term_forfeited);
    }

    #[Test]
    public function testCalculatesMultiplePenaltyTermsCorrectly(): void
    {
        $petition = Petition::factory()->create();
        $today = CalendarDate::today();

        $penalty1 = 50;
        $penalty2 = 75;
        $duration1 = 3;
        $duration2 = 4;

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::PENALTY,
            'start_date' => $today,
            'duration_in_days' => $duration1,
            'penalty_amount_in_euros' => $penalty1,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::PENALTY,
            'start_date' => $today,
            'duration_in_days' => $duration2,
            'penalty_amount_in_euros' => $penalty2,
        ]);

        $this->action->execute($petition);
        $petition->refresh();

        $expectedTodaySum = $penalty1 + $penalty2;
        $expectedMaximum = ($penalty1 * $duration1) + ($penalty2 * $duration2);

        $this->assertEquals($expectedTodaySum, $petition->legacy_term_penalty_today);
        $this->assertEquals($expectedMaximum, $petition->legacy_term_penalty_maximum);
    }

    #[Test]
    public function testDoesNothingWhenPetitionIsTermEngineConverted(): void
    {
        $petition = Petition::factory()->create([
            'deadline_at' => CalendarDate::create('2025-01-01'),
            'total_days_suspended' => 10,
            'legacy_term_penalty_today' => 100,
        ]);

        PetitionEvent::factory()->create(['petition_id' => $petition->id]);

        $originalDeadlineAt = $petition->deadline_at;
        $originalTotalDaysSuspended = $petition->total_days_suspended;
        $originalLegacyPenalty = $petition->legacy_term_penalty_today;

        $this->action->execute($petition);
        $petition->refresh();

        $this->assertEquals($originalDeadlineAt, $petition->deadline_at);
        $this->assertEquals($originalTotalDaysSuspended, $petition->total_days_suspended);
        $this->assertEquals($originalLegacyPenalty, $petition->legacy_term_penalty_today);
    }

    #[Test]
    public function testResetsIgsAndBntFieldsToZero(): void
    {
        $petition = Petition::factory()->create([
            'igs_penalty_today' => 50,
            'igs_forfeited' => 75,
            'igs_penalty_maximum' => 100,
            'bnt_penalty_today' => 60,
            'bnt_forfeited' => 80,
            'bnt_penalty_maximum' => 110,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::FIRST,
            'start_date' => CalendarDate::create('2025-01-01'),
            'duration_in_days' => 5,
        ]);

        $this->action->execute($petition);
        $petition->refresh();

        $this->assertEquals(0, $petition->igs_penalty_today);
        $this->assertEquals(0, $petition->igs_forfeited);
        $this->assertEquals(0, $petition->igs_penalty_maximum);
        $this->assertEquals(0, $petition->bnt_penalty_today);
        $this->assertEquals(0, $petition->bnt_forfeited);
        $this->assertEquals(0, $petition->bnt_penalty_maximum);
    }

    #[Test]
    public function testHandlesPenaltyTermsNotActiveToday(): void
    {
        $petition = Petition::factory()->create();
        $today = CalendarDate::today();
        $futureDateStart = $today->addDays(5);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::PENALTY,
            'start_date' => $futureDateStart,
            'duration_in_days' => 3,
            'penalty_amount_in_euros' => 100,
        ]);

        $this->action->execute($petition);
        $petition->refresh();

        $this->assertEquals(0, $petition->legacy_term_penalty_today);
        $this->assertEquals(100 * 3, $petition->legacy_term_penalty_maximum);
    }

    #[Test]
    public function testCombinesSuspensionsAndPenaltiesCorrectly(): void
    {
        $petition = Petition::factory()->create();
        $today = CalendarDate::today();

        $suspensionDays = 20;
        $penaltyAmount = 80;
        $penaltyDuration = 5;

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::SUSPENSION,
            'start_date' => CalendarDate::create('2025-01-15'),
            'duration_in_days' => $suspensionDays,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::PENALTY,
            'start_date' => $today,
            'duration_in_days' => $penaltyDuration,
            'penalty_amount_in_euros' => $penaltyAmount,
        ]);

        PetitionTerm::factory()->create([
            'petition_id' => $petition->id,
            'type' => TermType::FIRST,
            'start_date' => CalendarDate::create('2025-01-01'),
            'duration_in_days' => 10,
        ]);

        $this->action->execute($petition);
        $petition->refresh();

        $this->assertEquals($suspensionDays, $petition->total_days_suspended);
        $this->assertEquals($penaltyAmount, $petition->legacy_term_penalty_today);
        $this->assertEquals($penaltyAmount * $penaltyDuration, $petition->legacy_term_penalty_maximum);
        $this->assertNotNull($petition->deadline_at);
    }

    #[Test]
    public function testHandlesEmptyTermCollection(): void
    {
        $petition = Petition::factory()->create([
            'deadline_at' => null,
        ]);

        $this->action->execute($petition);
        $petition->refresh();

        $this->assertNull($petition->deadline_at);
        $this->assertEquals(0, $petition->total_days_suspended);
        $this->assertEquals(0, $petition->legacy_term_penalty_today);
        $this->assertEquals(0, $petition->legacy_term_forfeited);
        $this->assertEquals(0, $petition->legacy_term_penalty_maximum);
    }
}
