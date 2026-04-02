<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Petition;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

use function sprintf;

/**
 * Command to correct a petition number (zaaknummer) in the petitions table.
 *
 * Usage: php artisan petitions:update-number --from=<old> --to=<new> [--commit]
 *
 * Sample
 * Do a dry run to preview the change without making any changes (default)
 * Usage: php artisan petitions:update-number --from=2025C00041 --to=2025000547
 *
 * Run the command and commit the change to the database
 * Usage: php artisan petitions:update-number --from=2025C00041 --to=2025000547 --commit
 */
class UpdatePetitionNumberCommand extends Command
{
    /** @var string $signature */
    protected $signature = 'petitions:update-number
                            {--from= : Current petition number (zaaknummer) to replace}
                            {--to=   : New petition number (zaaknummer) to set}
                            {--commit : Commit changes to database (default is dry-run)}';

    /** @var string $description */
    protected $description = 'Correct a petition number (zaaknummer) for a single petition';

    public function handle(): int
    {
        $isDryRun = !$this->option('commit');
        $from = $this->option('from');
        $to = $this->option('to');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to the database');
        }

        if (empty($from) || empty($to)) {
            $this->error('Both --from and --to options are required.');

            return self::FAILURE;
        }

        if ($from === $to) {
            $this->error('--from and --to are identical; nothing to update.');

            return self::FAILURE;
        }

        $petition = Petition::query()->where('number', $from)->first();

        if ($petition === null) {
            $this->error(sprintf('No petition found with number "%s".', $from));

            return self::FAILURE;
        }

        $this->info(sprintf('Found petition: id=%s, number=%s', $petition->id, $petition->number));
        $this->info(sprintf('From: %s', $from));
        $this->info(sprintf('To:   %s', $to));

        if ($isDryRun) {
            $this->info('Run with --commit to apply the change.');

            return self::SUCCESS;
        }

        try {
            DB::beginTransaction();

            Petition::query()
                ->where('number', $from)
                ->update(['number' => $to]);

            DB::commit();

            $this->info(sprintf(
                'Successfully updated petition number from "%s" to "%s".',
                $from,
                $to,
            ));

            return self::SUCCESS;
        // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error(sprintf('Error updating petition number: %s', $e->getMessage()));

            return self::FAILURE;
        }
        // @codeCoverageIgnoreEnd
    }
}
