<?php

declare(strict_types=1);

namespace App\Services\Terms;

use App\Models\PetitionTerm;
use App\ValueObjects\CalendarDate;
use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;

class DeadlineCalculator
{
    /**
     * @param Collection<int, PetitionTerm> $collection
     */
    public function calculateDeadline(Collection $collection): CalendarDate
    {
        $deadline = $collection
            ->filter(static function ($item) {
                return $item->type->isDeadlineable();
            })
            ->max('end_date');

        Assert::isInstanceOf($deadline, CalendarDate::class);

        return $deadline;
    }
}
