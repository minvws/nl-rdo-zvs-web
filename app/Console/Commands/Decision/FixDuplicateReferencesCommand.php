<?php

declare(strict_types=1);

namespace App\Console\Commands\Decision;

use App\Models\Decision;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Throwable;

use function sprintf;
use function str_contains;
use function str_starts_with;
use function trim;

#[Signature('app:decisions:fix-references')]
#[Description('Fix duplicate and null/empty decision references')]
final class FixDuplicateReferencesCommand extends Command
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $decisions = Decision::query()
            ->select(['id', 'reference', 'created_at'])->oldest()
            ->get();

        if ($decisions->isEmpty()) {
            $this->info('No decisions found.');

            return Command::SUCCESS;
        }

        $toUpdate = $this->buildUpdates($decisions);

        if ($toUpdate->isEmpty()) {
            $this->info('No issues found.');

            return Command::SUCCESS;
        }

        try {
            $this->persistUpdates($toUpdate);
        } catch (Throwable $e) {
            $this->reportFailure($toUpdate, $e);

            return Command::FAILURE;
        }

        $this->reportResults(
            $this->countGenerated($toUpdate),
            $this->countDuplicates($toUpdate),
        );

        return Command::SUCCESS;
    }

    /**
     * @param Collection<int, Decision> $decisions
     *
     * @return Collection<int, array{model: Decision, reference: string}>
     */
    private function buildUpdates(Collection $decisions): Collection
    {
        $groupedByReference = $decisions
            ->filter(static fn(Decision $decision): bool => $decision->reference !== null && trim($decision->reference) !== '')
            ->groupBy(static fn(Decision $decision): string => (string) $decision->reference);

        return $decisions->map(function (Decision $decision, int $key) use ($groupedByReference): ?array {
            $reference = $decision->reference;
            $isEmpty = $reference === null || trim($reference) === '';

            if ($isEmpty) {
                return $this->buildEmptyReferenceUpdate($decision, $key + 1);
            }

            $normalizedReference = (string) $reference;
            $group = $groupedByReference->get($normalizedReference);

            if ($group === null || $group->count() === 1) {
                return null;
            }

            return $this->buildDuplicateReferenceUpdate($decision, $group, $normalizedReference);
        })->filter();
    }

    /**
     * @return array{model: Decision, reference: string}
     */
    private function buildEmptyReferenceUpdate(Decision $decision, int $number): array
    {
        return [
            'model' => $decision,
            'reference' => sprintf('_zvs_gegenereerde_id_%05d', $number),
        ];
    }

    /**
     * @param Collection<int, Decision> $group
     *
     * @return array{model: Decision, reference: string}|null
     */
    private function buildDuplicateReferenceUpdate(Decision $decision, Collection $group, string $normalizedReference): ?array
    {
        $groupedDecisions = $group->sortBy('created_at')->values();
        $position = $groupedDecisions->search(static fn(Decision $d): bool => $d->id === $decision->id);

        // @codeCoverageIgnoreStart
        if ($position === false) {
            return null;
        }

        // @codeCoverageIgnoreEnd
        if ($position === 0) {
            return [
                'model' => $decision,
                'reference' => $normalizedReference,
            ];
        }

        $suffix = sprintf(' (dubbel ingevoerd %d)', $position);

        return [
            'model' => $decision,
            'reference' => $normalizedReference . $suffix,
        ];
    }

    /**
     * @param Collection<int, array{model: Decision, reference: string}> $toUpdate
     */
    private function persistUpdates(Collection $toUpdate): void
    {
        $this->databaseManager->transaction(static function () use ($toUpdate): void {
            $toUpdate->each(static function (array $update): void {
                $update['model']->reference = $update['reference'];
                $update['model']->save();
            });
        });
    }

    /**
     * @param Collection<int, array{model: Decision, reference: string}> $toUpdate
     */
    private function reportFailure(Collection $toUpdate, Throwable $e): void
    {
        $failedIds = $toUpdate->map(
            static fn(array $update): string => (string) $update['model']->id,
        )->implode(', ');

        $this->error(sprintf(
            'Failed to update references. Affected decision IDs: [%s]. Error: %s',
            $failedIds,
            $e->getMessage(),
        ));
    }

    /**
     * @param Collection<int, array{model: Decision, reference: string}> $toUpdate
     */
    private function countGenerated(Collection $toUpdate): int
    {
        return $toUpdate->filter(
            static fn(array $update): bool => str_starts_with($update['reference'], '_zvs_gegenereerde_id_'),
        )->count();
    }

    /**
     * @param Collection<int, array{model: Decision, reference: string}> $toUpdate
     */
    private function countDuplicates(Collection $toUpdate): int
    {
        return $toUpdate->filter(
            static fn(array $update): bool => str_contains($update['reference'], '(dubbel ingevoerd'),
        )->count();
    }

    private function reportResults(int $generated, int $duplicates): void
    {
        $this->info(sprintf(
            'Processed %d decisions: %d generated, %d fixed with duplicate suffix.',
            $generated + $duplicates,
            $generated,
            $duplicates,
        ));
    }
}
