<?php

declare(strict_types=1);

namespace App\Console\Commands\Petition;

use App\Models\Petition;
use App\Models\PetitionCategory;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

use function array_keys;
use function array_values;
use function assert;
use function sprintf;

#[Description('Reassign petitions from one category to another based on a fixed mapping list')]
#[Signature('petitions:reassign-categories
    {--commit : Commit changes to database (default is dry-run)}')]
class ReassignPetitionCategoriesCommand extends Command
{
    /** @var array<string, string> */
    private array $mappings = [
        'subsidieregeling Tegemoetkoming amateursportorgani' => 'Subsidieregeling tegemoetkoming amateursportorgani',
        'Subsidieregeling Stageplaatsenzorg II' => 'Subsidieregeling stageplaatsen zorg',
        'Subsidieregeling tegemoetkoming verhuurders sporta' => 'Subsidieregeling tegemoetkoming verhuurders amateursportorgani',
        'Alcoholwet Lcsh' => 'Alcoholwet LCSH',
        'AWB' => 'Awb',
        'Instellingssubsidie Patiënten en gehandicaptenorga' => 'Subsidieregeling PG organisaties 2024-2028',
        'Subsidieregeling pg organisaties 2024-2028' => 'Subsidieregeling PG organisaties 2024-2028',
        'subsidieregeling VIPP inzicht PGO' => 'Subsidieregeling VIPP inzicht PGO',
        'Subsidieverlening PrEP' => 'Subsidieregeling PrEP',
    ];

    public function handle(): int
    {
        $isDryRun = !$this->option('commit');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to the database');
        }

        $fromNames = array_keys($this->mappings);
        $toNames = array_values($this->mappings);

        $fromCategories = PetitionCategory::query()->withoutGlobalScopes()
            ->whereIn('name', $fromNames)
            ->get();

        $toCategories = PetitionCategory::query()->withoutGlobalScopes()
            ->whereIn('name', $toNames)
            ->get();

        $fromCategoryIds = $fromCategories->pluck('id');

        $petitionCountsByCategoryId = Petition::query()
            ->whereIn('petition_category_id', $fromCategoryIds)
            ->selectRaw('petition_category_id, COUNT(*) as count')
            ->groupBy('petition_category_id')
            ->pluck('count', 'petition_category_id');

        $rows = [];
        $hasError = false;

        foreach ($this->mappings as $from => $to) {
            $fromCats = $fromCategories->where('name', $from);

            if ($fromCats->isEmpty()) {
                $rows[] = [$from, $to, '-', 0, 'No from-category found'];
                continue;
            }

            foreach ($fromCats as $fromCat) {
                $toCategory = $toCategories->first(
                    static fn (PetitionCategory $cat): bool => $cat->name === $to && $cat->department_id === $fromCat->department_id,
                );

                if ($toCategory === null) {
                    $rows[] = [$from, $to, (string) $fromCat->department_id, 0, 'No to-category found in same department'];
                    $hasError = true;
                    continue;
                }

                $count = $petitionCountsByCategoryId->get((string) $fromCat->id, 0);

                $rows[] = [$from, $to, (string) $fromCat->department_id, $count, 'OK'];
            }
        }

        $this->table(
            ['From category', 'To category', 'Department', 'Petitions', 'Status'],
            $rows,
        );

        if ($isDryRun) {
            $this->info('Run with --commit to apply the changes.');

            return self::SUCCESS;
        }

        if ($hasError) {
            $this->error('Some mappings could not be resolved. Fix the issues above before committing.');

            return self::FAILURE;
        }

        try {
            DB::beginTransaction();

            $totalReassigned = 0;

            foreach ($this->mappings as $from => $to) {
                foreach ($fromCategories->where('name', $from) as $fromCat) {
                    $toCategory = $toCategories->first(
                        static fn (PetitionCategory $cat): bool => $cat->name === $to && $cat->department_id === $fromCat->department_id,
                    );

                    assert($toCategory !== null);

                    $totalReassigned += Petition::query()->where('petition_category_id', $fromCat->id)
                        ->update(['petition_category_id' => $toCategory->id]);
                }
            }

            DB::commit();

            $this->info(sprintf('Successfully reassigned %d petition(s).', $totalReassigned));

            return self::SUCCESS;
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error(sprintf('Error reassigning categories: %s', $e->getMessage()));

            return self::FAILURE;
        }
    }
}
