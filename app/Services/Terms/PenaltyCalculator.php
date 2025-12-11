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
            ->filter(static function ($item): bool {
                return $item->type === TermType::PENALTY;
            })
            ->sum(static function ($item): int {
                return $item->duration_in_days * $item->penalty_amount_in_euros;
            });
    }

    /**
     * @param Collection<int, PetitionTerm> $collection
     */
    public function calculatePenaltyAmountToDate(CalendarDate $date, Collection $collection): int
    {
        return $collection
            ->sortBy('start_date')
            ->filter(static function ($item): bool {
                return $item->type === TermType::PENALTY;
            })
            ->filter(static function ($item) use ($date): bool {
                return $item->start_date <= $date;
            })
            ->sum(static function ($item) use ($date): int {
                return TermDateCalculator::calculateDuration($item->start_date, $date) * $item->penalty_amount_in_euros;
            });
    }
}
