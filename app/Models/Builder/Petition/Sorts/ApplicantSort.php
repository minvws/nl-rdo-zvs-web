<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Sorts;

use App\Models\Contact;
use App\Models\Petition;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

/**
 * @implements Sort<Petition>
 */
class ApplicantSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $query->orderBy(
            Contact::query()->select('type')->whereColumn('id', 'applicant_id'),
            $descending ? 'desc' : 'asc',
        );
    }
}
