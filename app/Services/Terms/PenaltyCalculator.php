<?php

declare(strict_types=1);

namespace App\Services\Terms;

use App\Enums\TermType;
use App\Models\PetitionTerm;
use App\ValueObjects\CalendarDate;
use Illuminate\Support\Collection;

class PenaltyCalculator
{
    /**
     * @param Collection<int, PetitionTerm> $collection
     */
    public function calculateTotalPenaltyAmount(Collection $collection): int
    {
        return $collection
            ->filter(static fn($item): bool => $item->type === TermType::PENALTY)
            ->sum(static fn($item): int => $item->duration_in_days * $item->penalty_amount_in_euros);
    }

    /**
     * @param Collection<int, PetitionTerm> $collection
     */
    public function calculatePenaltyAmountToDate(CalendarDate $date, Collection $collection): int
    {
        return $collection
            ->sortBy('start_date')
            ->filter(static fn($item): bool => $item->type === TermType::PENALTY)
            ->filter(static fn($item): bool => $item->start_date <= $date)
            ->sum(
                static fn($item): int => TermDateCalculator::calculateDuration($item->start_date, $date) * $item->penalty_amount_in_euros,
            );
    }
}
