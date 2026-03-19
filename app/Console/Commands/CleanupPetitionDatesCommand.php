<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Petition;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

use function preg_match;
use function sprintf;

/**
 * Command to clean up invalid dates in the petitions table.
 *
 * Usage: php artisan petitions:cleanup-dates [--date=yyyy-mm-dd] [--commit]
 *
 * Sample
 * Do a dry run to preview the changes without making any changes (default)
 * Usage: php artisan petitions:cleanup-dates
 * Usage: php artisan petitions:cleanup-dates --date=2025-04-14
 *
 * Run the command and commit the changes to the database
 * Usage: php artisan petitions:cleanup-dates --commit
 * Usage: php artisan petitions:cleanup-dates --date=2025-04-14 --commit
 */
class CleanupPetitionDatesCommand extends Command
{
    /** @var string $signature */
    protected $signature = 'petitions:cleanup-dates
                            {--date=2025-04-14 : Target date to replace with null (format: yyyy-mm-dd)}
                            {--commit : Commit changes to database (default is dry-run)}';

    /** @var string $description */
    protected $description = 'Set deadline_at and date_of_entry to null where value matches target date';

    public function handle(): int
    {
        $isDryRun = !$this->option('commit');
        $targetDate = $this->option('date');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to the database');
        }

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $targetDate)) {
            $this->error('Invalid date format. Please use yyyy-mm-dd format.');

            return self::FAILURE;
        }

        $this->info(sprintf('Target date: %s', $targetDate));

        try {
            // Count records that need updating
            $deadlineCount = Petition::query()
                ->where('deadline_at', $targetDate)
                ->count();

            $dateOfEntryCount = Petition::query()
                ->where('date_of_entry', $targetDate)
                ->count();

            if ($deadlineCount === 0 && $dateOfEntryCount === 0) {
                $this->info(sprintf('No records found with date %s. Nothing to update.', $targetDate));

                return self::SUCCESS;
            }

            $this->info(sprintf(
                'Found %d record(s) with deadline_at = %s',
                $deadlineCount,
                $targetDate,
            ));
            $this->info(sprintf(
                'Found %d record(s) with date_of_entry = %s',
                $dateOfEntryCount,
                $targetDate,
            ));

            if ($isDryRun) {
                $this->info(sprintf(
                    "\nWould update %d total record(s). Run with --commit to apply changes.",
                    $deadlineCount + $dateOfEntryCount,
                ));

                return self::SUCCESS;
            }

            // Perform updates in a transaction
            DB::beginTransaction();

            $updatedDeadlineCount = Petition::query()
                ->where('deadline_at', $targetDate)
                ->update(['deadline_at' => null]);

            DB::commit();

            $this->info(sprintf(
                'Successfully updated %d record(s) with deadline_at = null',
                $updatedDeadlineCount,
            ));

            $this->info(sprintf(
                'Total: %d record(s) updated',
                $updatedDeadlineCount,
            ));

            return self::SUCCESS;
        } catch (Throwable $e) {
            if (!$isDryRun) {
                DB::rollBack();
            }
            $this->error(sprintf('Error cleaning up petition dates: %s', $e->getMessage()));

            return self::FAILURE;
        }
    }
}
