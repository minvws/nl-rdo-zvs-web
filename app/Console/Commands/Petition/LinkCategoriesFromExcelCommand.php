<?php

declare(strict_types=1);

namespace App\Console\Commands\Petition;

use App\Models\Petition;
use App\Models\PetitionCategory;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use stdClass;
use Throwable;

use function assert;
use function collect;
use function file_exists;
use function is_scalar;
use function sprintf;
use function str_replace;
use function strtolower;
use function trim;

#[Description('Link categories to petitions by zaaknummer from an Excel file with columns "Zaaknummer" and "Categorie"')]
#[Signature('petitions:link-categories-from-excel
    {file : Path to the Excel file}
    {--commit : Commit changes to database (default is dry-run)}')]
class LinkCategoriesFromExcelCommand extends Command
{
    /** @var array<string, string> */
    protected array $columnMapping = [
        'zaaknummer' => 'number',
        'categorie' => 'category_name',
    ];

    public function handle(): int
    {
        /** @var string $filePath */
        $filePath = $this->argument('file');
        $isDryRun = !$this->option('commit');

        if (!file_exists($filePath)) {
            $this->error(sprintf('File not found: %s', $filePath));

            return self::FAILURE;
        }

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to the database');
        }

        try {
            /** @var Collection<int, mixed|Collection<int, mixed|Collection<int, mixed>>> $rawData */
            $rawData = Excel::toCollection(new stdClass(), $filePath);
        } catch (Throwable $e) {
            $this->error(sprintf('Error reading Excel file: %s', $e->getMessage()));

            return self::FAILURE;
        }

        $sheetData = $rawData->get(0, collect());
        if (!$sheetData instanceof Collection || $sheetData->isEmpty()) {
            $this->error('Excel file is empty');

            return self::FAILURE;
        }

        $firstRow = $sheetData->first();
        if (!$firstRow instanceof Collection) {
            $this->error('Excel file has an invalid header row');

            return self::FAILURE;
        }

        $headers = $firstRow->map(
            fn($header): string => $this->transformColumnName(is_scalar($header) ? (string) $header : ''),
        )->values();

        if (!$this->hasRequiredColumns($headers)) {
            $this->error('Excel file must contain the "Zaaknummer" and "Categorie" columns.');

            return self::FAILURE;
        }

        $rows = $sheetData->slice(1)->filter(static fn($r): bool => $r instanceof Collection)->values();
        $tableRows = [];
        $hasError = false;
        $totalToLink = 0;

        foreach ($rows as $row) {
            $filteredRow = $row->filter(static fn($value): bool => $value !== null && $value !== '');
            if ($filteredRow->isEmpty()) {
                continue;
            }

            $data = $this->combineRow($headers, $row);
            $petitionNumber = trim((string) ($data['number'] ?? ''));
            $categoryName = trim($data['category_name'] ?? '');

            if ($petitionNumber === '') {
                $tableRows[] = ['-', '-', '-', 'Missing petition number'];
                $hasError = true;
                continue;
            }

            if ($categoryName === '') {
                $tableRows[] = [$petitionNumber, '-', '-', 'Missing category name'];
                $hasError = true;
                continue;
            }

            $petition = Petition::query()->where('number', $petitionNumber)->first();
            if ($petition === null) {
                $tableRows[] = [$petitionNumber, '-', $categoryName, 'Petition not found'];
                $hasError = true;
                continue;
            }

            $category = PetitionCategory::query()
                ->withoutGlobalScopes()
                ->where('name', $categoryName)
                ->where('department_id', $petition->department_id)
                ->first();

            $petitionCategory = $petition->petitionCategory;
            $currentName = $petitionCategory !== null ? $petitionCategory->name : '-';

            if ($category === null) {
                $tableRows[] = [$petitionNumber, $currentName, $categoryName, 'Category not found in department'];
                $hasError = true;
                continue;
            }

            if (
                $petition->petition_category_id !== null
                && (string) $petition->petition_category_id === (string) $category->id
            ) {
                $tableRows[] = [$petitionNumber, $currentName, $categoryName, 'No change needed'];
                continue;
            }

            $tableRows[] = [$petitionNumber, $currentName, $categoryName, 'OK'];
            $totalToLink++;
        }

        $this->table(
            ['Zaaknummer', 'Huidige categorie', 'Nieuwe categorie', 'Status'],
            $tableRows,
        );

        if ($isDryRun) {
            $this->info(sprintf('Dry run completed. Would link %d petition(s).', $totalToLink));
            $this->info('Run with --commit to apply the changes.');

            return self::SUCCESS;
        }

        if ($hasError) {
            $this->error('Some petitions or categories could not be resolved. Fix the issues above before committing.');

            return self::FAILURE;
        }

        try {
            DB::beginTransaction();

            $totalLinked = 0;

            foreach ($rows as $row) {
                $filteredRow = $row->filter(static fn($value): bool => $value !== null && $value !== '');
                if ($filteredRow->isEmpty()) {
                    continue;
                }

                $data = $this->combineRow($headers, $row);
                $petitionNumber = trim((string) ($data['number'] ?? ''));
                $categoryName = trim($data['category_name'] ?? '');

                $petition = Petition::query()->where('number', $petitionNumber)->first();
                assert($petition !== null);

                $category = PetitionCategory::query()
                    ->withoutGlobalScopes()
                    ->where('name', $categoryName)
                    ->where('department_id', $petition->department_id)
                    ->first();
                assert($category !== null);

                if (
                    $petition->petition_category_id !== null
                    && (string) $petition->petition_category_id === (string) $category->id
                ) {
                    continue;
                }

                Petition::query()->where('number', $petitionNumber)->update(['petition_category_id' => $category->id]);
                $totalLinked++;
            }

            DB::commit();

            $this->info(sprintf('Successfully linked %d petition(s).', $totalLinked));

            return self::SUCCESS;
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error(sprintf('Error linking categories: %s', $e->getMessage()));

            return self::FAILURE;
        }
    }

    protected function transformColumnName(string $excelColumn): string
    {
        $normalized = strtolower(trim($excelColumn));

        if (isset($this->columnMapping[$normalized])) {
            return $this->columnMapping[$normalized];
        }

        return strtolower(str_replace([' ', '.'], '_', $excelColumn));
    }

    /**
     * @param Collection<int, string> $headers
     */
    private function hasRequiredColumns(Collection $headers): bool
    {
        return $headers->contains('number') && $headers->contains('category_name');
    }

    /**
     * @param Collection<int, string> $headers
     * @param Collection<int|string, mixed> $row
     *
     * @return Collection<string, string|null>
     */
    private function combineRow(Collection $headers, Collection $row): Collection
    {
        return $headers->mapWithKeys(static function ($key, $index) use ($row): array {
            $value = $row->get($index);

            return [$key => is_scalar($value) ? (string) $value : null];
        });
    }
}
