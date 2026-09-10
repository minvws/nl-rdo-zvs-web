<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Filters;

use App\Models\Petition;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\QueryBuilder\Filters\Filter;

use function is_string;

/**
 * Filters on the deadline the user actually sees, so an overridden deadline filters on the
 * override and everything else on the calculated deadline.
 *
 * @implements Filter<Petition>
 */
class DeadlineFilter implements Filter
{
    public function __construct(private readonly string $operator)
    {
    }

    public function __invoke(Builder $query, mixed $value, string $property = ''): void
    {
        if (!is_string($value)) {
            return;
        }

        try {
            $date = CalendarDate::createFromFormat(CalendarDate::DEFAULT_STRING_FORMAT, $value);
        } catch (InvalidArgumentException) {
            return;
        }

        $query->where(
            DB::raw('COALESCE(deadline_override_at, deadline_at)'),
            $this->operator,
            $date->format(CalendarDate::DEFAULT_STRING_FORMAT),
        );
    }
}
