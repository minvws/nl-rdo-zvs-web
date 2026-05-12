<?php

declare(strict_types=1);

namespace App\Console\Commands\Petition;

use App\Models\Petition;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

use function sprintf;

#[Signature('petitions:update-number
    {--from= : Current petition number (zaaknummer) to replace}
    {--to=   : New petition number (zaaknummer) to set}
    {--commit : Commit changes to database (default is dry-run)}')]
#[Description('Correct a petition number (zaaknummer) for a single petition')]
class UpdatePetitionNumberCommand extends Command
{
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
