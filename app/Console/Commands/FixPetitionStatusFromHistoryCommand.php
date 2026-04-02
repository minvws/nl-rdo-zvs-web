<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

use function sprintf;

/**
 * Command to fix petitions whose petition_status_id does not match the most recent
 * non-future entry in petition_statuses_history_entries.
 *
 * Usage (dry-run, default):
 *   php artisan petitions:fix-status-from-history
 *
 * Usage (apply changes):
 *   php artisan petitions:fix-status-from-history --commit
 */
class FixPetitionStatusFromHistoryCommand extends Command
{
    /** @var string $signature */
    protected $signature = 'petitions:fix-status-from-history
                            {--commit : Commit changes to database (default is dry-run)}';

    /** @var string $description */
    protected $description = 'Set petition_status_id to the status from the most recent non-future history entry where they differ';

    public function handle(): int
    {
        $isDryRun = !$this->option('commit');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to the database');
        }

        try {
            /** @var int $count */
            $count = DB::scalar('
                SELECT COUNT(*)
                FROM petitions p
                JOIN (
                    SELECT DISTINCT ON (petition_id)
                        petition_id,
                        petition_status_id
                    FROM petition_statuses_history_entries
                    WHERE date <= CURRENT_DATE
                    ORDER BY petition_id, date DESC, created_at DESC
                ) AS latest ON p.id = latest.petition_id
                WHERE p.petition_status_id IS DISTINCT FROM latest.petition_status_id
            ');

            if ($count === 0) {
                $this->info('No mismatched petitions found. Nothing to update.');

                return self::SUCCESS;
            }

            $this->info(sprintf('Found %d petition(s) with a mismatched status.', $count));

            if ($isDryRun) {
                $this->info(sprintf('Would update %d petition(s). Run with --commit to apply changes.', $count));

                return self::SUCCESS;
            }

            DB::beginTransaction();

            $updated = DB::update('
                UPDATE petitions
                SET petition_status_id = latest.petition_status_id
                FROM (
                    SELECT DISTINCT ON (petition_id)
                        petition_id,
                        petition_status_id
                    FROM petition_statuses_history_entries
                    WHERE date <= CURRENT_DATE
                    ORDER BY petition_id, date DESC, created_at DESC
                ) AS latest
                WHERE petitions.id = latest.petition_id
                  AND petitions.petition_status_id IS DISTINCT FROM latest.petition_status_id
            ');

            DB::commit();

            $this->info(sprintf('Successfully updated %d petition(s).', $updated));

            return self::SUCCESS;
        } catch (Throwable $e) {
            if (!$isDryRun) {
                DB::rollBack();
            }
            $this->error(sprintf('Error fixing petition statuses: %s', $e->getMessage()));

            return self::FAILURE;
        }
    }
}
