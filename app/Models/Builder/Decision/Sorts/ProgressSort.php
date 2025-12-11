<?php

declare(strict_types=1);

namespace App\Models\Builder\Decision\Sorts;

use App\Enums\ProcessingStepStatus;
use App\Models\Decision;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class ProgressSort implements Sort
{
    /**
     * @param Builder<Decision> $query
     */
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $query->addSelect([
            'total_steps' => static function (\Illuminate\Database\Query\Builder $query): void {
                $query->selectRaw('COUNT(processing_steps.id)')
                    ->from('processing_steps')
                    ->whereColumn('processing_steps.decision_id', 'decisions.id');
            },
            'completed_steps' => static function (\Illuminate\Database\Query\Builder $query): void {
                $query->selectRaw('COUNT(processing_steps.id)')
                    ->from('processing_steps')
                    ->whereColumn('processing_steps.decision_id', 'decisions.id')
                    ->where('processing_steps.status', ProcessingStepStatus::CLOSED->value);
            },
            'completion_percentage' => static function (\Illuminate\Database\Query\Builder $query): void {
                $query->selectRaw('
                    CASE
                        WHEN COUNT(processing_steps.id) = 0 THEN 0
                        ELSE (COUNT(CASE WHEN processing_steps.status = ? THEN 1 END) * 100.0) / COUNT(processing_steps.id)
                    END
                ', [ProcessingStepStatus::CLOSED->value])
                    ->from('processing_steps')
                    ->whereColumn('processing_steps.decision_id', 'decisions.id');
            },
        ]);

        $query->orderBy('completion_percentage', $descending ? 'desc' : 'asc')
            ->orderBy('total_steps', $descending ? 'desc' : 'asc');
    }
}
