<?php

declare(strict_types=1);

namespace App\Console\Commands\Petition;

use App\Enums\AssignmentRole;
use App\Enums\CustomDateLabel;
use App\Models\Contact;
use App\Models\ContactPetition;
use App\Models\CustomPetitionProperty;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionAssignment;
use App\Models\PetitionCustomDate;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\PolicyDepartment;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

use function array_combine;
use function array_filter;
use function array_map;
use function array_search;
use function array_slice;
use function count;
use function date;
use function explode;
use function file_exists;
use function in_array;
use function is_int;
use function json_encode;
use function now;
use function sprintf;
use function str_replace;
use function strtolower;
use function time;
use function trim;

#[Signature('petitions:import-appeals
    {file? : Path to Excel file to import}
    {--commit : Commit changes to database (default is dry-run)}
    {--rollback= : Rollback import by batch ID}
    {--file-jurist= : Excel file mapping jurist names to email addresses}')]
#[Description('Import an Excel file with appeals (beroepen) and insert data into petition table')]
class ImportAppealsCommand extends Command
{
    /**
     * Column mapping from beroepen Excel column names to database field names.
     *
     * @var array<string, string>
     */
    protected array $columnMapping = [
        'juist kenmerk' => 'number',
        'naam' => 'bezwaarde',
        'directie' => 'beleidsafdeling',
        'soort' => 'petition_type_id',
        'jurist' => 'jurist',
        'uitspraak' => 'uitspraak',
        'datum uitspraak' => 'datum_uitspraak',
        'binnenkomst' => 'date_of_entry',
        'zitting' => 'zitting',
    ];

    /** @var array<int, array<string, mixed>> */
    private array $failedRecords = [];

    /** @var array<string, array<int>> */
    private array $importedIds = [];

    private string $batchId = '';

    /** @var array<string, string> */
    private array $juristEmailMapping = [];

    public function handle(): int
    {
        if ($this->option('rollback')) {
            return $this->handleRollback($this->option('rollback'));
        }

        return $this->handleImport();
    }

    private function handleImport(): int
    {
        /** @var string $filePath */
        $filePath = $this->argument('file');
        $isDryRun = !$this->option('commit');

        if (!file_exists($filePath)) {
            $this->error(sprintf('File not found: %s', $filePath));

            return self::FAILURE;
        }

        $juristFilePath = $this->option('file-jurist');
        if ($juristFilePath) {
            if (!file_exists($juristFilePath)) {
                $this->error(sprintf('Jurist file not found: %s', $juristFilePath));

                return self::FAILURE;
            }
            $this->loadJuristEmailMapping($juristFilePath);
        }

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to the database');
        }

        $this->batchId = 'import_' . time();

        try {
            $rawData = Excel::toArray((object) [], $filePath);
            $sheetData = $rawData[0] ?? [];

            if (count($sheetData) === 0) {
                $this->error('Excel file is empty');

                return self::FAILURE;
            }

            $headers = array_map(
                fn ($header): string => $this->transformColumnName((string) $header),
                $sheetData[0],
            );
            $rows = array_slice($sheetData, 1);

            $department = Department::query()->where('slug', 'wjz-bb')->first();
            if (!$department) {
                $this->error('Department "wjz-bb" not found');

                return self::FAILURE;
            }

            $insertedCount = 0;
            $rowNumber = 1;
            foreach ($rows as $row) {
                $rowNumber++;
                $filtered = array_filter(
                    $row,
                    static fn ($value): bool => $value !== null && $value !== '',
                );
                if (count($filtered) === 1) {
                    continue;
                }

                $data = array_combine($headers, $row);
                if (!isset($data['number'])) {
                    continue;
                }
                if (!$data['number']) {
                    continue;
                }

                $data['department_id'] = $department->id;

                // Get user_id from jurist name
                if (isset($data['jurist']) && $data['jurist']) {
                    $user = User::query()->where('name', 'ILIKE', $data['jurist'])->first();

                    if (!$user && isset($this->juristEmailMapping[$data['jurist']])) {
                        $email = $this->juristEmailMapping[$data['jurist']];
                        $user = User::query()->where('email', $email)->first();

                        if ($user) {
                            $this->line(sprintf(
                                '  Jurist "%s" found by email: %s',
                                $data['jurist'],
                                $email,
                            ));
                        }
                    }

                    if (!$user) {
                        $this->failedRecords[] = [
                            'row' => $rowNumber,
                            'reason' => sprintf('Jurist not found: %s', $data['jurist']),
                            'petition_number' => $data['number'],
                        ];
                        continue;
                    }
                    $data['jurist_user_id'] = $user->id;
                    unset($data['jurist']);
                }

                // Get petition_type_id from soort
                if (isset($data['petition_type_id']) && $data['petition_type_id']) {
                    $petitionType = PetitionType::query()
                        ->where('name', $data['petition_type_id'])
                        ->where('department_id', $department->id)
                        ->first();
                    if (!$petitionType) {
                        $this->failedRecords[] = [
                            'row' => $rowNumber,
                            'reason' => sprintf('Petition type not found: %s', $data['petition_type_id']),
                            'petition_number' => $data['number'],
                        ];
                        continue;
                    }
                    $data['petition_type_id'] = $petitionType->id;

                    // Map uitspraak to status
                    if (isset($data['uitspraak']) && $data['uitspraak']) {
                        $uitspraakStatusMapping = [
                            'afgewezen' => 'Uitspraak',
                            'doorzending' => 'Beroep doorgezonden',
                            'gegrond' => 'Uitspraak',
                            'instantie verklaart zich onbevoegd' => 'Uitspraak',
                            'intrekking' => 'Beroep ingetrokken',
                            'kennelijk niet-ontvankelijk' => 'Uitspraak',
                            'niet-ontvankelijk' => 'Uitspraak',
                            'ongegrond' => 'Uitspraak',
                            'toegewezen' => 'Toebedeling',
                            'uitspraak' => 'Uitspraak',
                        ];

                        $uitspraakLower = strtolower((string) $data['uitspraak']);
                        $mappedStatus = $uitspraakStatusMapping[$uitspraakLower] ?? null;

                        if ($mappedStatus) {
                            $petitionStatus = PetitionStatus::query()
                                ->where('status', 'ILIKE', $mappedStatus)
                                ->where('petition_type_id', $petitionType->id)
                                ->first();

                            if ($petitionStatus) {
                                $data['petition_status_id'] = $petitionStatus->id;
                            }
                        }
                    }

                    // If no status was set via uitspraak, check if zitting has a date
                    if (!isset($data['petition_status_id']) && isset($data['zitting']) && $data['zitting']) {
                        $zittingStatus = PetitionStatus::query()
                            ->where('status', 'Zitting')
                            ->where('petition_type_id', $petitionType->id)
                            ->first();

                        if ($zittingStatus) {
                            $data['petition_status_id'] = $zittingStatus->id;
                        }
                    }

                    // If no status was set, default to Toebedeling
                    if (!isset($data['petition_status_id'])) {
                        $firstStatus = PetitionStatus::query()
                            ->where('status', 'Toebedeling')
                            ->where('petition_type_id', $petitionType->id)
                            ->first();

                        if ($firstStatus) {
                            $data['petition_status_id'] = $firstStatus->id;
                        }
                    }
                }

                $petition = Petition::query()->where('number', $data['number'])->first();
                if (!$petition) {
                    $petitionData = array_filter([
                        'number' => $data['number'],
                        'date_of_entry' => isset($data['date_of_entry']) ? $this->convertDateFormat(
                            $data['date_of_entry'],
                        ) : null,
                        'petition_status_id' => $data['petition_status_id'] ?? null,
                        'petition_type_id' => $data['petition_type_id'] ?? null,
                        'department_id' => $data['department_id'],
                    ], static fn ($value): bool => $value !== null);

                    if (!isset($petitionData['date_of_entry'])) {
                        $this->failedRecords[] = [
                            'row' => $rowNumber,
                            'reason' => 'date_of_entry is required',
                            'petition_number' => $data['number'],
                        ];
                        continue;
                    }

                    if (!isset($petitionData['petition_status_id'])) {
                        $this->failedRecords[] = [
                            'row' => $rowNumber,
                            'reason' => 'petition_status_id could not be determined (no matching status found for petition type)',
                            'petition_number' => $data['number'],
                        ];
                        continue;
                    }

                    if ($isDryRun) {
                        $this->line(sprintf(
                            'Would create petition: %s',
                            json_encode($petitionData),
                        ));
                        $petition = new Petition($petitionData);
                        $petition->id = Str::uuid();
                        $petition->petition_type_id = $data['petition_type_id'];
                        $insertedCount++;
                    } else {
                        $petition = Petition::query()->create($petitionData);
                        $this->importedIds['petitions'][] = $petition->id;
                        $insertedCount++;
                    }
                } else {
                    $petitionUpdateData = array_filter([
                        'date_of_entry' => isset($data['date_of_entry']) ? $this->convertDateFormat(
                            $data['date_of_entry'],
                        ) : null,
                        'petition_status_id' => $data['petition_status_id'] ?? null,
                        'petition_type_id' => $data['petition_type_id'] ?? null,
                    ], static fn ($value): bool => $value !== null);

                    if ($petitionUpdateData !== []) {
                        if ($isDryRun) {
                            $this->line(sprintf(
                                'Would update petition %s: %s',
                                $petition->number,
                                json_encode($petitionUpdateData),
                            ));
                        } else {
                            $petition->update($petitionUpdateData);
                            $this->line(sprintf('  Updated petition: %s', $petition->number));
                        }
                    }
                }

                // Handle jurist: create PRIMARY assignment
                if (isset($data['jurist_user_id'])) {
                    $alreadyAssigned = PetitionAssignment::query()
                        ->where('petition_id', $petition->id)
                        ->where('assignment_role', AssignmentRole::PRIMARY)
                        ->exists();

                    if (!$alreadyAssigned) {
                        if ($isDryRun) {
                            $this->line('  Would assign jurist as primary handler');
                        } else {
                            $assignment = PetitionAssignment::query()->create([
                                'petition_id' => $petition->id,
                                'user_id' => $data['jurist_user_id'],
                                'assignment_role' => AssignmentRole::PRIMARY,
                            ]);
                            $this->importedIds['petition_assignments'][] = $assignment->id;
                        }
                    }
                }

                // Handle Bezwaarde: get or create contact
                if (isset($data['bezwaarde']) && $data['bezwaarde']) {
                    $contact = Contact::query()->where('last_name', $data['bezwaarde'])->first();

                    if (!$contact) {
                        $contact = Contact::query()->create([
                            'last_name' => $data['bezwaarde'],
                            'department_id' => $department->id,
                            'notes' => 'Imported from batchid:' . $this->batchId,
                            'type' => 'burger',
                        ]);
                        $this->importedIds['contacts'][] = $contact->id;
                    }

                    if ($contact) {
                        $contactPetition = ContactPetition::query()
                            ->where('petition_id', $petition->id)
                            ->where('contact_id', $contact->id)
                            ->where('role', 'applicant')
                            ->first();

                        if (!$contactPetition) {
                            if ($isDryRun) {
                                $this->line(sprintf(
                                    '  Would link contact "%s" to petition with role=applicant',
                                    $data['bezwaarde'],
                                ));
                            } else {
                                $created = ContactPetition::query()->create([
                                    'petition_id' => $petition->id,
                                    'contact_id' => $contact->id,
                                    'role' => 'applicant',
                                    'reference' => '',
                                ]);
                                $this->importedIds['contact_petitions'][] = $created->id;
                            }
                        }
                    } else { // @codeCoverageIgnore
                        $this->failedRecords[] = [ // @codeCoverageIgnore
                            'row' => $rowNumber, // @codeCoverageIgnore
                            'reason' => sprintf('Contact not found: %s', $data['bezwaarde']), // @codeCoverageIgnore
                            'petition_number' => $data['number'], // @codeCoverageIgnore
                        ]; // @codeCoverageIgnore
                    } // @codeCoverageIgnore

                    unset($data['bezwaarde']);
                }

                // Handle Beleidsafdeling
                if (isset($data['beleidsafdeling']) && $data['beleidsafdeling']) {
                    $policyDepartmentNames = array_map(trim(...), explode(',', (string) $data['beleidsafdeling']));
                    $policyDepartmentNames = array_filter($policyDepartmentNames, static fn ($name): bool => $name !== '');

                    foreach ($policyDepartmentNames as $policyDepartmentName) {
                        $policyDepartment = PolicyDepartment::query()
                            ->where('name', 'ILIKE', $policyDepartmentName)
                            ->first();

                        if ($policyDepartment) {
                            if (!$isDryRun) {
                                $exists = DB::table('petition_policy_department')
                                    ->where('petition_id', $petition->id)
                                    ->where('policy_department_id', $policyDepartment->id)
                                    ->exists();
                                if (!$exists) {
                                    DB::table('petition_policy_department')->insert([
                                        'petition_id' => $petition->id,
                                        'policy_department_id' => $policyDepartment->id,
                                    ]);
                                }
                            } else {
                                $this->line(sprintf(
                                    '  Would link policy department "%s"',
                                    $policyDepartmentName,
                                ));
                            }
                        } else {
                            $this->failedRecords[] = [
                                'row' => $rowNumber,
                                'reason' => sprintf('Policy department not found: %s', $policyDepartmentName),
                                'petition_number' => $data['number'],
                            ];
                        }
                    }

                    unset($data['beleidsafdeling']);
                }

                // Map uitspraak to reden intrekking
                $uitspraakMapping = [
                    'afgewezen' => 'Ongegrond',
                    'doorzending' => 'Doorzending',
                    'informeel' => 'Informeel',
                    'gegrond' => 'Gegrond',
                    'instantie verklaart zich onbevoegd' => 'Rechtbank onbevoegd',
                    'intrekking' => 'Intrekking',
                    'kennelijk niet-ontvankelijk' => 'Kennelijk niet-ontvankelijk',
                    'niet-ontvankelijk' => 'Niet-ontvankelijk',
                    'ongegrond' => 'Ongegrond',
                    'toegewezen' => 'Gegrond',
                ];

                if (isset($data['uitspraak']) && $data['uitspraak']) {
                    $uitspraakLower = strtolower((string) $data['uitspraak']);
                    $data['redenintrekking'] = $uitspraakMapping[$uitspraakLower] ?? $data['uitspraak'];
                }

                if (isset($data['redenintrekking']) && $data['redenintrekking']) {
                    $redenIntrekkingProperty = CustomPetitionProperty::query()
                        ->where('petition_type_id', $petition->petition_type_id)
                        ->where('type', 'option')
                        ->where('name', $data['redenintrekking'])
                        ->first();

                    if ($redenIntrekkingProperty) {
                        if (!$isDryRun) {
                            $propertyExists = DB::table('custom_petition_property_petition')
                                ->where('petition_id', $petition->id)
                                ->where('custom_petition_property_id', $redenIntrekkingProperty->id)
                                ->exists();
                            if (!$propertyExists) {
                                DB::table('custom_petition_property_petition')->insert([
                                    'petition_id' => $petition->id,
                                    'custom_petition_property_id' => $redenIntrekkingProperty->id,
                                ]);
                            }
                        } else {
                            $this->line(sprintf(
                                '  Would add Reden intrekking: %s',
                                $data['redenintrekking'],
                            ));
                        }
                    }

                    unset($data['redenintrekking']);
                }

                // Handle datum uitspraak and zitting
                $dateMapping = [
                    'datum_uitspraak' => CustomDateLabel::DATE_RULING,
                    'zitting' => CustomDateLabel::DATE_COURT_SESSION,
                ];

                foreach ($dateMapping as $excelColumn => $dateLabel) {
                    if (isset($data[$excelColumn]) && $data[$excelColumn]) {
                        $formattedDate = $this->convertDateFormat($data[$excelColumn]);

                        $existingCustomDate = PetitionCustomDate::query()
                            ->where('petition_id', $petition->id)
                            ->where('date_label', $dateLabel)
                            ->first();

                        if (!$existingCustomDate && $formattedDate) {
                            if ($isDryRun) {
                                $this->line(sprintf(
                                    '  Would add custom date %s: %s',
                                    $excelColumn,
                                    $formattedDate,
                                ));
                            } else {
                                $created = PetitionCustomDate::query()->create([
                                    'petition_id' => $petition->id,
                                    'date_label' => $dateLabel,
                                    'date' => $formattedDate,
                                ]);
                                $this->importedIds['petition_custom_dates'][] = $created->id;
                            }
                        } elseif (!$formattedDate && $data[$excelColumn]) {
                            $this->failedRecords[] = [
                                'row' => $rowNumber,
                                'reason' => sprintf('Invalid date format for %s: %s', $excelColumn, $data[$excelColumn]),
                                'petition_number' => $data['number'],
                            ];
                        }
                    }

                    unset($data[$excelColumn]);
                }
            }

            if (!$isDryRun && $insertedCount > 0) {
                Cache::put($this->batchId, $this->importedIds, now()->addDays(30));
                $this->info(sprintf('Batch ID: %s (stored for 30 days)', $this->batchId));
            }

            if ($isDryRun) {
                $this->info(sprintf(
                    'Dry run completed. Would import %d new petitions',
                    $insertedCount,
                ));
            } else {
                $this->info(sprintf('Successfully imported %d rows into petition table', $insertedCount));
            }

            if (count($this->failedRecords) > 0) {
                $this->warn(sprintf(
                    "\n %d row(s) failed to import:",
                    count($this->failedRecords),
                ));
                foreach ($this->failedRecords as $failed) {
                    $this->line(sprintf(
                        '  Row %d: %s',
                        $failed['row'],
                        $failed['reason'],
                    ));
                    if (isset($failed['petition_number'])) {
                        $this->line(sprintf('    Petition: %s', $failed['petition_number']));
                    }
                }
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error(sprintf('Error importing Excel file: %s', $e->getMessage()));

            return self::FAILURE;
        }
    }

    private function handleRollback(string $batchId): int
    {
        $importedIds = Cache::get($batchId);

        if (!$importedIds) {
            $this->error(sprintf('Batch ID not found: %s', $batchId));
            $this->info('Available batches can be found in cache for up to 30 days after import');

            return self::FAILURE;
        }

        $this->warn(sprintf('Rolling back import batch: %s', $batchId));

        DB::beginTransaction();

        try {
            $deletedCount = 0;

            if (isset($importedIds['petition_custom_dates'])) {
                $count = PetitionCustomDate::query()->whereIn('id', $importedIds['petition_custom_dates'])->delete();
                $deletedCount += $count;
                $this->info(sprintf('Deleted %d petition custom dates', $count));
            }

            if (isset($importedIds['petition_assignments'])) {
                $count = PetitionAssignment::query()->whereIn('id', $importedIds['petition_assignments'])->delete();
                $deletedCount += $count;
                $this->info(sprintf('Deleted %d petition assignments', $count));
            }

            if (isset($importedIds['contact_petitions'])) {
                $count = ContactPetition::query()->whereIn('id', $importedIds['contact_petitions'])->delete();
                $deletedCount += $count;
                $this->info(sprintf('Deleted %d contact petitions', $count));
            }

            if (isset($importedIds['contacts'])) {
                $count = Contact::query()->whereIn('id', $importedIds['contacts'])->delete();
                $deletedCount += $count;
                $this->info(sprintf('Deleted %d contacts', $count));
            }

            if (isset($importedIds['petitions'])) {
                foreach ($importedIds['petitions'] as $petitionId) {
                    $petition = Petition::query()->find($petitionId);
                    if (!$petition) {
                        continue;
                    }

                    $petition->policyDepartments()->detach();
                    $petition->customPetitionProperties()->detach();
                }
            }

            if (isset($importedIds['petitions'])) {
                $count = Petition::query()->whereIn('id', $importedIds['petitions'])->delete();
                $deletedCount += $count;
                $this->info(sprintf('Deleted %d petitions', $count));
            }

            DB::commit();
            Cache::forget($batchId);

            $this->info(sprintf('Rollback completed. Deleted %d total records', $deletedCount));

            return self::SUCCESS;
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error(sprintf('Rollback failed: %s', $e->getMessage()));

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

    protected function convertDateFormat(string|int $date): ?string
    {
        if (in_array($date, ['', '0', 0], true)) {
            return null;
        }

        if (is_int($date)) {
            try {
                $unixTimestamp = ($date - 25_569) * 86_400;

                return date('Y-m-d', $unixTimestamp);
            } catch (Throwable) { // @codeCoverageIgnore
                return null; // @codeCoverageIgnore
            } // @codeCoverageIgnore
        }

        try {
            $calendarDate = CalendarDate::createFromFormat('j-n-Y', $date);

            return $calendarDate->format('Y-m-d');
        } catch (Throwable) {
            try {
                $calendarDate = CalendarDate::createFromFormat('n/j/y', $date);

                return $calendarDate->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }
    }

    private function loadJuristEmailMapping(string $filePath): void
    {
        try {
            $rawData = Excel::toArray((object) [], $filePath);
            $sheetData = $rawData[0] ?? [];

            if (count($sheetData) === 0) {
                $this->warn('Jurist Excel file is empty');

                return;
            }

            $headers = array_map(
                static fn ($header) => strtolower(trim((string) $header)),
                $sheetData[0],
            );

            $nameIndex = array_search('name', $headers, true);
            $emailIndex = array_search('email', $headers, true);

            if ($nameIndex === false || $emailIndex === false) {
                $this->warn('Jurist file must contain "name" and "email" columns');

                return;
            }

            $rows = array_slice($sheetData, 1);
            foreach ($rows as $row) {
                $name = $row[$nameIndex] ?? null;
                $email = $row[$emailIndex] ?? null;

                if ($name && $email) {
                    $this->juristEmailMapping[(string) $name] = (string) $email;
                }
            }

            $this->info(sprintf(
                'Loaded %d jurist email mappings from file',
                count($this->juristEmailMapping),
            ));
        } catch (Throwable $e) {
            $this->warn(sprintf('Failed to load jurist email mapping: %s', $e->getMessage()));
        }
    }
}
