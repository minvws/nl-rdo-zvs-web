<?php

declare(strict_types=1);

namespace App\Console\Commands\Petition;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\TimelineItem;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Ramsey\Uuid\Uuid;
use Throwable;

use function array_filter;
use function array_map;
use function array_values;
use function sprintf;

#[Signature('petitions:restore-assignees
    {--commit : Commit changes to database (default is dry-run)}')]
#[Description('Set the correct assignee (assigned_to) from the most recent assignment_occurrence in the timeline')]
class RestoreAssigneesCommand extends Command
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun = !$this->option('commit');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to the database');
        }

        try {
            $this->databaseManager->beginTransaction();

            $petitions = Petition::query()
                ->whereHas('timelineItems', static fn ($q) => $q->where('type', TimelineType::ASSIGNMENT_OCCURRENCE))
                ->with('assignedUser')
                ->get();

            if ($petitions->isEmpty()) {
                $this->info('No petitions found with assignment_occurrence timeline items. Nothing to update.');

                $this->databaseManager->rollBack();

                return self::SUCCESS;
            }

            $rows = [];

            foreach ($petitions as $petition) {
                /** @var TimelineItem $latest */
                $latest = $petition->timelineItems()
                    ->where('type', TimelineType::ASSIGNMENT_OCCURRENCE)
                    ->orderByDesc('updated_at')
                    ->first();

                /** @var string|null $correctUserId */
                $correctUserId = $latest->data['current_assigned_user_id'] ?? null;

                /** @var User|null $newUser */
                $newUser = $correctUserId !== null ? User::query()->find($correctUserId) : null;

                $wijziging = match (true) {
                    $correctUserId !== null && $newUser === null => false,
                    default => (string) $petition->assigned_to !== (string) $correctUserId,
                };

                $rows[] = [
                    'zaaknummer' => $petition->number,
                    'wijziging' => $wijziging,
                    'petition' => $petition,
                    'correctUserId' => $correctUserId,
                    'newUser' => $newUser,
                ];
            }

            $changedRows = array_values(array_filter($rows, static fn (array $row): bool => $row['wijziging']));

            $tableRows = array_map(
                static function (array $row): array {
                    /** @var Petition $petition */
                    $petition = $row['petition'];
                    $newUser = $row['newUser'];

                    $currentName = $petition->assignedUser !== null ? $petition->assignedUser->name : '(geen)';
                    $newName = $newUser instanceof User ? $newUser->name : '(geen)';

                    return [$row['zaaknummer'], $currentName, $newName];
                },
                $changedRows,
            );

            $this->table(['Zaaknummer', 'Huidige behandelaar', 'Nieuwe behandelaar'], $tableRows);

            $updateCount = 0;

            foreach ($rows as $row) {
                if (!$row['wijziging']) {
                    continue;
                }

                $updateCount++;
                $petition = $row['petition'];
                $correctUserId = $row['correctUserId'];
                $petition->assigned_to = $correctUserId !== null ? Uuid::fromString($correctUserId) : null;
                $petition->save();
            }

            if ($isDryRun) {
                $this->databaseManager->rollBack();
                $this->info(sprintf(
                    'Would update %d petition(s). Run with --commit to apply changes.',
                    $updateCount,
                ));
            } else {
                $this->databaseManager->commit();
                $this->info(sprintf(
                    'Successfully updated %d petition(s).',
                    $updateCount,
                ));
            }

            return self::SUCCESS;
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            $this->databaseManager->rollBack();
            $this->error(sprintf('Error fixing petitions assignee: %s', $e->getMessage()));

            return self::FAILURE;
        }
        // @codeCoverageIgnoreEnd
    }
}
