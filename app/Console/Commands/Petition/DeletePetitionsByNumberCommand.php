<?php

declare(strict_types=1);

namespace App\Console\Commands\Petition;

use App\Models\Petition;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

use function array_diff;
use function sprintf;

#[Description('Permanently delete petitions by zaaknummer, including all related data')]
#[Signature('petition:delete-by-numbers {numbers* : Zaaknummers to delete}')]
class DeletePetitionsByNumberCommand extends Command
{
    public function handle(): int
    {
        /** @var array<string> $numbers */
        $numbers = $this->argument('numbers');

        $petitions = Petition::query()
            ->whereIn('number', $numbers)
            ->get();

        /** @var array<string> $foundNumbers */
        $foundNumbers = $petitions->pluck('number')->all();
        $notFound = array_diff($numbers, $foundNumbers);

        if ($notFound !== []) {
            $this->warn('The following zaaknummers were NOT found in the database:');
            foreach ($notFound as $number) {
                $this->line('  - ' . $number);
            }
        }

        if ($petitions->isEmpty()) {
            $this->error('No petitions found. Aborting.');

            return self::FAILURE;
        }

        $this->info(sprintf('Found %d petition(s) to delete:', $petitions->count()));
        foreach ($petitions as $petition) {
            $this->line(sprintf('  - %s (id: %s)', $petition->number, $petition->id));
        }

        if (
            !$this->confirm(
                'Are you sure you want to PERMANENTLY delete these petitions and all related data? This cannot be undone.',
                false,
            )
        ) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($petitions): void {
                foreach ($petitions as $petition) {
                    // Detach BelongsToMany pivot tables that may have RESTRICT constraints
                    $petition->policyDepartments()->detach();
                    $petition->decisions()->detach();
                    $petition->customPetitionProperties()->detach();
                    $petition->relatedPetitions()->detach();

                    // Delete HasMany relations that may have RESTRICT constraints
                    $petition->petitionEvents()->delete();

                    // Delete the petition (CASCADE handles remaining HasMany relations)
                    $petition->delete();

                    $this->line(sprintf('  Deleted: %s', $petition->number));
                }
            });

            $this->info('All petitions successfully deleted.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Error during deletion: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
