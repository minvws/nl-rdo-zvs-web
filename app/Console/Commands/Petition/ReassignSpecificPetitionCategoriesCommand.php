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

use function assert;
use function sprintf;

#[Description('Reassign the category of specific petitions by zaaknummer based on a fixed mapping list')]
#[Signature('petitions:reassign-specific-categories
    {--commit : Commit changes to database (default is dry-run)}')]
class ReassignSpecificPetitionCategoriesCommand extends Command
{
    /** @var array<array-key, string> */
    private array $mappings = [
        '2021000524' => 'Geneesmiddelenwet (CIBG)',
        '2021000584' => 'Wet publieke gezondheid',
        '2021000688' => 'Subsidieregeling SOIT',
        '2021000752' => 'Subsidieregeling SOIT',
        '2021000771' => 'Subsidieregeling SOIT',
        '2021000773' => 'Subsidieregeling SOIT',
        '2021000774' => 'Subsidieregeling SOIT',
        '2021000775' => 'Subsidieregeling SOIT',
        '2021000776' => 'Subsidieregeling SOIT',
        '2021000777' => 'Subsidieregeling SOIT',
        '2022000005' => 'Wet publieke gezondheid',
        '2022000051' => 'Subsidieregeling SOIT',
        '2022000282' => 'Subsidieregeling SOIT',
        '2021000914' => 'Wet marktordening gezondheidzorg (wmg)',
    ];

    public function handle(): int
    {
        $isDryRun = !$this->option('commit');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to the database');
        }

        $rows = [];
        $hasError = false;

        foreach ($this->mappings as $number => $categoryName) {
            $petition = Petition::query()->where('number', (string) $number)->first();

            if ($petition === null) {
                $rows[] = [$number, $categoryName, 'No petition found'];
                $hasError = true;
                continue;
            }

            $category = PetitionCategory::query()->withoutGlobalScopes()
                ->where('name', $categoryName)
                ->where('department_id', $petition->department_id)
                ->first();

            if ($category === null) {
                $rows[] = [$number, $categoryName, 'Category not found in petition department'];
                $hasError = true;
                continue;
            }

            $petitionCategory = $petition->petitionCategory;
            $currentName = $petitionCategory !== null ? $petitionCategory->name : '-';
            $rows[] = [$number, $currentName, $categoryName, 'OK'];
        }

        $this->table(
            ['Zaaknummer', 'Current category', 'New category', 'Status'],
            $rows,
        );

        if ($isDryRun) {
            $this->info('Run with --commit to apply the changes.');

            return self::SUCCESS;
        }

        if ($hasError) {
            $this->error('Some petitions or categories could not be resolved. Fix the issues above before committing.');

            return self::FAILURE;
        }

        try {
            DB::beginTransaction();

            $totalReassigned = 0;

            foreach ($this->mappings as $number => $categoryName) {
                $petition = Petition::query()->where('number', (string) $number)->first();
                assert($petition !== null);

                $category = PetitionCategory::query()->withoutGlobalScopes()
                    ->where('name', $categoryName)
                    ->where('department_id', $petition->department_id)
                    ->first();
                assert($category !== null);

                Petition::query()->where('number', (string) $number)->update(['petition_category_id' => $category->id]);
                $totalReassigned++;
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
