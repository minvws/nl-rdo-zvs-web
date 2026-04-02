<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\TimelineItem;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Ramsey\Uuid\Uuid;
use Throwable;

use function sprintf;

/**
 * Command to restore all assignee (assigned_to) on petitions based on the most recent
 * assignment_occurrence timeline item.
 *
 * Usage: php artisan petitions:restore-assignees [--commit]
 *
 * Do a dry run to preview the changes without making any changes (default):
 * Usage: php artisan petitions:restore-assignees
 *
 * Run the command and commit the changes to the database:
 * Usage: php artisan petitions:restore-assignees --commit
 */
class RestoreAssigneesCommand extends Command
{
    /** @var string $signature */
    protected $signature = 'petitions:restore-assignees
                            {--commit : Commit changes to database (default is dry-run)}';

    /** @var string $description */
    protected $description = 'Set the correct assignee (assigned_to) from the most recent assignment_occurrence in the timeline';

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
                ->get();

            if ($petitions->isEmpty()) {
                $this->info('No petitions found with assignment_occurrence timeline items. Nothing to update.');

                $this->databaseManager->rollBack();

                return self::SUCCESS;
            }

            $updateCount = 0;

            foreach ($petitions as $petition) {
                /** @var TimelineItem $latest */
                $latest = $petition->timelineItems()
                    ->where('type', TimelineType::ASSIGNMENT_OCCURRENCE)
                    ->first();

                /** @var string|null $correctUserId */
                $correctUserId = $latest->data['current_assigned_user_id'] ?? null;

                if ((string) $petition->assigned_to === (string) $correctUserId) {
                    continue;
                }

                $updateCount++;
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
