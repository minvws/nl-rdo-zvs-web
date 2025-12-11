<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Filters;

use App\Models\Petition;
use App\ValueObjects\DateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * @implements Filter<Petition>
 */
class PetitionStatusHistoryDateRangeFilter implements Filter
{
    /** @param DateRange $value */
    public function __invoke(Builder $query, mixed $value, string $property = ''): void
    {
        $dateFrom = $value->getStartDate()->format('Y-m-d');
        $dateTo = $value->getEndDate()->format('Y-m-d');

        $query1 = DB::table('petition_statuses_history_entries as h')
            ->leftJoin('petition_statuses as s', 'h.petition_status_id', '=', 's.id')
            ->whereBetween('h.date', [$dateFrom, $dateTo])
            ->where('s.status_group', 'pending')
            ->select('h.petition_id')
            ->groupBy('h.petition_id');


        $subquery = DB::table('petition_statuses_history_entries as h')
            ->leftJoin('petition_statuses as s', 'h.petition_status_id', '=', 's.id')
            ->where('h.date', '<', $dateFrom)
            ->selectRaw('DISTINCT ON (h.petition_id) h.petition_id, s.status_group')
            ->orderBy('h.petition_id')
            ->orderBy('h.date', 'desc')
            ->latest('h.created_at');

        $query2 = DB::query()
            ->fromSub($subquery, 'query2')
            ->select('query2.petition_id')
            ->where('query2.status_group', 'pending');

        $query->whereIn('id', $query1->union($query2));
    }
}
