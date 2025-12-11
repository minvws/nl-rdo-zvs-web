<?php

declare(strict_types=1);

namespace App\Models\Builder\Decision\Sorts;

use App\Models\Decision;
use App\Models\ProcessingStep;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class DeadlineSort implements Sort
{
    /**
     * @param Builder<Decision> $query
     */
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $today = CalendarDate::today()->toDateString();

        $query->addSelect([
            'earliest_deadline' => ProcessingStep::query()
                ->select('deadline_at')
                ->whereColumn('decision_id', 'decisions.id')
                ->where('deadline_at', '>=', $today)
                ->oldest('deadline_at')
                ->limit(1),
        ]);

        $query->{ $descending ? 'orderByDesc' : 'orderBy' }('earliest_deadline');
    }
}
