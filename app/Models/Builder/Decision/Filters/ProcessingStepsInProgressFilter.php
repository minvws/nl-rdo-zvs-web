<?php

declare(strict_types=1);

namespace App\Models\Builder\Decision\Filters;

use App\Enums\ProcessingStepStatus;
use App\Models\Decision;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;
use Webmozart\Assert\Assert;

/**
 * @implements Filter<Decision>
 */
class ProcessingStepsInProgressFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        Assert::string($value);

        $query->whereHas('processingSteps', static function (Builder $subQuery) use ($value): void {
            $subQuery
                ->where('name', $value)
                ->where('status', ProcessingStepStatus::PENDING);
        });
    }
}
