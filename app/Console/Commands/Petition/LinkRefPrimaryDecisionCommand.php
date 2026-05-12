<?php

declare(strict_types=1);

namespace App\Console\Commands\Petition;

use App\Enums\ContactRole;
use App\Models\ContactPetition;
use App\Models\Petition;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use stdClass;
use Throwable;

use function collect;
use function file_exists;
use function is_scalar;
use function sprintf;
use function str_replace;
use function strtolower;
use function trim;

/**
 * Command to link the "Kenmerk Primair Besluit" from an Excel sheet to petitions and applicants.
 *
 * Usage: php artisan petitions:link-ref-primary-decision /path/to/file.xlsx [--commit]
 *
 * Sample
 * Do a dry run to preview the changes without making any changes (default)
 * Usage: php artisan petitions:link-ref-primary-decision storage/imports/data.xlsx
 *
 * Run the command and commit the changes to the database
 * Usage: php artisan petitions:link-ref-primary-decision storage/imports/data.xlsx --commit
 */
class LinkRefPrimaryDecisionCommand extends Command
{
    /** @var string $signature */
    protected $signature = 'petitions:link-ref-primary-decision
                            {file : Path to Excel file to import}
                            {--commit : Commit changes to database (default is dry-run)}';

    /** @var string $description */
    protected $description = 'Link the "Kenmerk Primair Besluit" to petition message and applicant reference fields';

    /** @var array<string, string> */
    protected array $columnMapping = [
        'zaaknummers' => 'number',
        'kenmerk primair besluit' => 'primary_decision_reference',
    ];

    /** @var array<int, array<string, int|string>> */
    private array $failedRecords = [];

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

        // Ensure the first row is an array (headers) and normalize keys to integer indexes
        $firstRow = $sheetData->first();
        if (!$firstRow instanceof Collection) {
            $this->error('Excel file has an invalid header row');

            return self::FAILURE;
        }

        $headers = $firstRow->map(
            fn($header): string => $this->transformColumnName(is_scalar($header) ? (string) $header : ''),
        )
            ->values();

        if (!$this->hasRequiredColumns($headers)) {
            $this->error('Excel file must contain the "Zaaknummers" and "Kenmerk Primair Besluit" columns.');

            return self::FAILURE;
        }

        // Only keep rows that are arrays (protects against malformed sheets)
        $rows = $sheetData->slice(1)->filter(static fn($r): bool => $r instanceof Collection)->values();
        $updatedPetitions = 0;
        $updatedApplicantLinks = 0;
        $rowNumber = 1;

        if (!$isDryRun) {
            DB::beginTransaction();
        }

        foreach ($rows as $row) {
            $rowNumber++;

            // Skip empty rows (all values are null or empty)
            $filteredRow = $row->filter(static fn($value): bool => $value !== null && $value !== '');
            if ($filteredRow->isEmpty()) {
                continue;
            }

            $data = $this->combineRow($headers, $row);
            $petitionNumber = trim($data['number'] ?? '');
            $primaryDecisionReference = trim($data['primary_decision_reference'] ?? '');

            if ($petitionNumber === '') {
                $this->failedRecords[] = [
                    'row' => $rowNumber,
                    'reason' => 'Missing value for column: Zaaknummers',
                ];
                continue;
            }

            if ($primaryDecisionReference === '') {
                $this->failedRecords[] = [
                    'row' => $rowNumber,
                    'reason' => 'Missing value for column: Kenmerk Primair Besluit',
                ];
                continue;
            }

            $petition = Petition::query()->where('number', $petitionNumber)->first();
            if ($petition === null) {
                $this->failedRecords[] = [
                    'row' => $rowNumber,
                    'reason' => sprintf('Petition not found: %s', $petitionNumber),
                ];
                continue;
            }

            if (!empty($petition->message)) {
                $this->failedRecords[] = [
                    'row' => $rowNumber,
                    'reason' => 'Petition already has something filled in the `message` column',
                ];
                continue;
            }

            $applicantLinkCount = ContactPetition::query()
                ->where('petition_id', $petition->id)
                ->where('role', ContactRole::APPLICANT->value)
                ->count();

            if ($isDryRun) {
                $this->line(sprintf(
                    'Would update petition %s with primary decision reference "%s"',
                    $petition->number,
                    $primaryDecisionReference,
                ));
                $this->line(sprintf('  Would update %d applicant link(s)', $applicantLinkCount));
                $updatedApplicantLinks += $applicantLinkCount;
            } else {
                $petition->update(['message' => $primaryDecisionReference]);
                $updatedApplicantLinks += ContactPetition::query()
                    ->where('petition_id', $petition->id)
                    ->where('role', ContactRole::APPLICANT->value)
                    ->where(static fn(Builder $q) => $q->whereNull('reference')->orWhere('reference', ''))
                    ->update(['reference' => $primaryDecisionReference]);
            }

            $updatedPetitions++;
        }

        if (!$isDryRun) {
            DB::commit();

            $this->info(sprintf(
                'Successfully updated %d petition(s) and %d applicant link(s).',
                $updatedPetitions,
                $updatedApplicantLinks,
            ));
        } else {
            $this->info(sprintf(
                'Dry run completed. Would update %d petition(s) and %d applicant link(s).',
                $updatedPetitions,
                $updatedApplicantLinks,
            ));
        }

        if (collect($this->failedRecords)->count() > 0) {
            $this->warn(sprintf(
                "\n%d row(s) could not be processed:",
                collect($this->failedRecords)->count(),
            ));

            foreach ($this->failedRecords as $failedRecord) {
                $this->line(sprintf(
                    '  Row %d: %s',
                    $failedRecord['row'],
                    $failedRecord['reason'],
                ));
            }
        }

        return self::SUCCESS;
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
        $cols = $headers;

        return $cols->contains('number') && $cols->contains('primary_decision_reference');
    }

    /**
     * @param Collection<int, string> $headers
     * @param Collection<int|string, mixed> $row
     *
     * @return Collection<string, string|null>
     */
    private function combineRow(Collection $headers, Collection $row): Collection
    {
        $headersCollection = $headers;
        $rowCollection = $row;

        // Map header keys to their corresponding row value (or null) and normalize to string|null
        return $headersCollection->mapWithKeys(static function ($key, $index) use ($rowCollection): array {
            $value = $rowCollection->get($index);

            return [$key => is_scalar($value) ? (string) $value : null];
        });
    }
}
