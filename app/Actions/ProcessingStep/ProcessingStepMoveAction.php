<?php

declare(strict_types=1);

namespace App\Actions\ProcessingStep;

use App\Enums\ProcessingStepMoveDirection;
use App\Models\ProcessingStep;
use Illuminate\Database\DatabaseManager;
use Throwable;

final readonly class ProcessingStepMoveAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    public function move(ProcessingStep $processingStep, ProcessingStepMoveDirection $direction): void
    {
        $this->databaseManager->transaction(function () use ($processingStep, $direction): void {
            $neighbour = $this->findNeighbour($processingStep, $direction);

            if (!$neighbour instanceof ProcessingStep) {
                return;
            }

            $this->swapOrderings($processingStep, $neighbour);
        });
    }

    private function findNeighbour(
        ProcessingStep $processingStep,
        ProcessingStepMoveDirection $direction,
    ): ?ProcessingStep {
        $isUp = $direction === ProcessingStepMoveDirection::UP;

        return ProcessingStep::query()
            ->where('decision_id', $processingStep->decision_id)
            ->where('ordering', $isUp ? '<' : '>', $processingStep->ordering)
            ->orderBy('ordering', $isUp ? 'desc' : 'asc')
            ->lockForUpdate()
            ->first();
    }

    /**
     * @throws Throwable
     */
    private function swapOrderings(ProcessingStep $step1, ProcessingStep $step2): void
    {
        $tempOrdering = $step1->ordering;

        $step1->ordering = $step2->ordering;
        $step2->ordering = $tempOrdering;

        $step1->saveOrFail();
        $step2->saveOrFail();
    }
}
