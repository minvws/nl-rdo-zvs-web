<?php

declare(strict_types=1);

namespace App\Models\Builder\Contact\Filters;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;
use Webmozart\Assert\Assert;

use function explode;

/**
 * @implements Filter<Contact>
 */
class SearchFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        Assert::string($value);
        $terms = explode(' ', $value);

        foreach ($terms as $searchTerm) {
            $query->where(static function (Builder $subQuery) use ($searchTerm): void {
                $subQuery->orWhere('last_name', 'ILIKE', '%' . $searchTerm . '%');
                $subQuery->orWhere('organisation_name', 'ILIKE', '%' . $searchTerm . '%');
                $subQuery->orWhere('email_address', 'ILIKE', '%' . $searchTerm . '%');
            });
        }
    }
}
