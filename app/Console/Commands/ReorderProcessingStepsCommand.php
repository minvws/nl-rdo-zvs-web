<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Decision;
use App\Models\ProcessingStep;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Throwable;

use function count;

#[Description('Reorder all processing steps by deadline_at and created_at per decision')]
#[Signature('processing-steps:reorder')]
final class ReorderProcessingStepsCommand extends Command
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $decisions = Decision::query()
            ->has('processingSteps')
            ->get();

        if ($decisions->isEmpty()) {
            $this->info('No decisions with processing steps found.');

            return Command::SUCCESS;
        }

        $totalReordered = 0;
        $failedDecisions = [];

        foreach ($decisions as $decision) {
            try {
                $reordered = $this->databaseManager->transaction(function () use ($decision): int {
                    return $this->reorderDecision($decision);
                });
                $totalReordered += $reordered;
            } catch (Throwable $e) {
                $failedDecisions[] = [
                    'id' => $decision->id,
                    'error' => $e->getMessage(),
                ];
                $this->logger->error('Failed to reorder processing steps', [
                    'decision_id' => $decision->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error('Failed to reorder decision ' . $decision->id . ': ' . $e->getMessage());
            }
        }

        $this->newLine();

        if ($failedDecisions !== []) {
            $this->logger->warning('Reordering completed with failures', [
                'total_reordered' => $totalReordered,
                'failed_count' => count($failedDecisions),
                'failed_decisions' => $failedDecisions,
            ]);
            $this->warn(
                count($failedDecisions) . ' decision(s) failed to reorder. ' . $totalReordered . ' step(s) reordered successfully.',
            );

            return Command::FAILURE;
        }

        $this->info('Processing steps have been reordered successfully.');

        return Command::SUCCESS;
    }

    private function reorderDecision(Decision $decision): int
    {
        $steps = $decision->processingSteps()
            ->reorder()
            ->oldest('deadline_at')
            ->oldest('created_at')
            ->get(['id', 'ordering', 'created_at']);

        $reorderedCount = 0;

        foreach ($steps as $index => $step) {
            $newOrdering = $index + 1;
            if ($step->ordering === $newOrdering) {
                continue;
            }

            ProcessingStep::query()->where('id', $step->id)
                ->update(['ordering' => $newOrdering]);
            $reorderedCount++;
        }

        return $reorderedCount;
    }
}
