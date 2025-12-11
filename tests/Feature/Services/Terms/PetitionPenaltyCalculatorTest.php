<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Terms;

use App\Enums\TermType;
use App\Models\PetitionTerm;
use App\Services\Terms\PenaltyCalculator;
use App\ValueObjects\CalendarDate;
use Tests\Feature\FeatureTestCase;

use function collect;

class PetitionPenaltyCalculatorTest extends FeatureTestCase
{
    public function testTotalPenaltyAmountNonePenaltyTerm(): void
    {
        $penaltyCalculator = new PenaltyCalculator();

        $collection = collect([
            PetitionTerm::factory()->make(
                [
                    'type' => TermType::FIRST,
                    'start_date' => '2025-04-01',
                    'duration_in_days' => 28,
                    'penalty_amount_in_euros' => 100,
                ],
            ),
        ]);

        $totalPenaltyAmount = $penaltyCalculator->calculateTotalPenaltyAmount($collection);

        $this->assertEquals(0, $totalPenaltyAmount);
    }

    public function testTotalPenaltyAmountOneTerm(): void
    {
        $penaltyCalculator = new PenaltyCalculator();

        $collection = collect([
            PetitionTerm::factory()->make(
                [
                    'type' => TermType::PENALTY,
                    'start_date' => '2025-04-01',
                    'duration_in_days' => 28,
                    'penalty_amount_in_euros' => 100,
                ],
            ),
        ]);

        $totalPenaltyAmount = $penaltyCalculator->calculateTotalPenaltyAmount($collection);

        $this->assertEquals(2800, $totalPenaltyAmount);
    }

    public function testTotalPenaltyAmountTwoTerms(): void
    {
        $penaltyCalculator = new PenaltyCalculator();

        $collection = collect([
            PetitionTerm::factory()->make(
                [
                    'type' => TermType::PENALTY,
                    'start_date' => '2025-04-01',
                    'duration_in_days' => 28,
                    'penalty_amount_in_euros' => 100,
                ],
            ),
            PetitionTerm::factory()->make(
                [
                    'type' => TermType::PENALTY,
                    'start_date' => '2025-05-01',
                    'duration_in_days' => 10,
                    'penalty_amount_in_euros' => 100,
                ],
            ),
        ]);

        $totalPenaltyAmount = $penaltyCalculator->calculateTotalPenaltyAmount($collection);

        $this->assertEquals(3800, $totalPenaltyAmount);
    }

    public function testToDatePenaltyAmountTwoTerms(): void
    {
        $date = CalendarDate::createFromFormat('Y-m-d', '2025-04-15');

        $penaltyCalculator = new PenaltyCalculator();

        $collection = collect([
            PetitionTerm::factory()->make(
                [
                    'type' => TermType::PENALTY,
                    'start_date' => '2025-04-01',
                    'duration_in_days' => 28,
                    'penalty_amount_in_euros' => 100,
                ],
            ),
            PetitionTerm::factory()->make(
                [
                    'type' => TermType::PENALTY,
                    'start_date' => '2025-05-01',
                    'duration_in_days' => 10,
                    'penalty_amount_in_euros' => 100,
                ],
            ),
        ]);

        $penaltyToDate = $penaltyCalculator->calculatePenaltyAmountToDate($date, $collection);

        $this->assertEquals(1500, $penaltyToDate);
    }

    public function testToDatePenaltyAmountTwoFutureTerms(): void
    {
        $date = CalendarDate::createFromFormat('Y-m-d', '2024-04-15');

        $penaltyCalculator = new PenaltyCalculator();

        $collection = collect([
            PetitionTerm::factory()->make(
                [
                    'type' => TermType::PENALTY,
                    'start_date' => '2025-04-01',
                    'duration_in_days' => 28,
                    'penalty_amount_in_euros' => 100,
                ],
            ),
            PetitionTerm::factory()->make(
                [
                    'type' => TermType::PENALTY,
                    'start_date' => '2025-05-01',
                    'duration_in_days' => 10,
                    'penalty_amount_in_euros' => 100,
                ],
            ),
        ]);

        $penaltyToDate = $penaltyCalculator->calculatePenaltyAmountToDate($date, $collection);

        $this->assertEquals(0, $penaltyToDate);
    }
}
