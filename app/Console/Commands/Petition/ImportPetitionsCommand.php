<?php

declare(strict_types=1);

namespace App\Console\Commands\Petition;

use App\Enums\CustomDateLabel;
use App\Models\Contact;
use App\Models\ContactPetition;
use App\Models\CustomPetitionProperty;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
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

/*
 * Command to import data from Excel into the database.
 *
 * Usage: php artisan petitions:import /path/to/file.xlsx [--commit] [--rollback=batch_id]
 *
 * Sample
 * Do a dry run to preview the import without making any changes (default)
 * Usage: php artisan petitions:import storage/imports/data.xlsx
 *
 * Run the import and commit changes to database
 * Usage: php artisan petitions:import storage/imports/data.xlsx --commit
 * Output: Batch ID: import_1738675200
 *
 * If something went wrong, rollback
 * Usage: php artisan petitions:import --rollback=import_1738675200
 */

#[Signature('petitions:import
    {file? : Path to Excel file to import}
    {--commit : Commit changes to database (default is dry-run)}
    {--rollback= : Rollback import by batch ID}
    {--file-jurist= : Excel file mapping jurist names to email addresses}
    {--file-category= : Excel file mapping category names when ZAAKNUMMER is empty}
    {--beroepen : Use beroepen column mapping instead of default}')]
#[Description('Import an Excel file and insert data into petition table')]
class ImportPetitionsCommand extends Command
{
    /**
     * Column mapping from Excel column names to database field names.
     * Add your mappings here in the format: 'Excel Column Name' => 'db_field_name'
     *
     * @var array<string, string>
     */
    protected array $columnMapping = [
        'zaaknummers' => 'number',
        'datum primair besluit' => 'date_appealed_decision',
        'ontvangstdatum bezwaar' => 'date_of_entry',
        'kenmerk primair besluit' => 'decision_reference',
        'jurist' => 'jurist',
        'bezwaarde' => 'bezwaarde',
        'categorie' => 'categorie',
        'beleidsafdeling' => 'beleidsafdeling',
        'status' => 'status',
        'dictum bob' => 'dictumbob',
        'reden intrekking' => 'redenintrekking',
        'zwaarte' => 'zwaarte',
        'datum bob' => 'datumbob',
        'datum intrekking' => 'datumintrekking',
        'datum doorzending' => 'datumdoorzending',
    ];

    /**
     * Column mapping for beroepen (appeals) import.
     * Maps Excel column names from beroepenWJZ file to database field names.
     *
     * @var array<string, string>
     */
    protected array $beroepenColumnMapping = [
        'juist kenmerk' => 'number',
        'naam' => 'bezwaarde',
        'directie' => 'beleidsafdeling',
        'wet' => 'categorie',
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

    /** @var array<string, string> Cache for jurist name to email mapping */
    private array $juristEmailMapping = [];

    /** @var array<string, string> Cache for category name mapping when ZAAKNUMMER is empty */
    private array $categoryMapping = [];

    public function handle(): int
    {
        // Handle rollback
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

        // Load jurist email mapping if file is provided
        $juristFilePath = $this->option('file-jurist');
        if ($juristFilePath) {
            if (!file_exists($juristFilePath)) {
                $this->error(sprintf('Jurist file not found: %s', $juristFilePath));
                return self::FAILURE;
            }
            $this->loadJuristEmailMapping($juristFilePath);
        }

        // Load category mapping if file is provided
        $categoryFilePath = $this->option('file-category');
        if ($categoryFilePath) {
            if (!file_exists($categoryFilePath)) {
                $this->error(sprintf('Category file not found: %s', $categoryFilePath));
                return self::FAILURE;
            }
            $this->loadCategoryMapping($categoryFilePath);
        }

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to the database');
        }

        if ($this->option('beroepen')) {
            $this->info('Using beroepen column mapping');
        }

        $this->batchId = 'import_' . time();

        try {
            $rawData = Excel::toArray([], $filePath);
            $sheetData = $rawData[0] ?? [];

            if (count($sheetData) === 0) {
                $this->error('Excel file is empty');

                return self::FAILURE;
            }

            // First row contains column names - transform using mapping
            // Use beroepen mapping if flag is set, otherwise use default mapping
            $useBeroepenMapping = $this->option('beroepen');
            $headers = array_map(
                fn ($header): string => $this->transformColumnName((string) $header, $useBeroepenMapping),
                $sheetData[0],
            );
            $rows = array_slice($sheetData, 1);

            // Get department_id for "WJZ Afdeling Bezwaar en Beroep"
            $department = Department::query()->where('slug', 'wjz-bb')->first();
            if (!$department) {
                $this->error('Department wjz-bb" not found');

                return self::FAILURE;
            }

            // Convert to array of objects with column names as keys
            $insertedCount = 0;
            $rowNumber = 1; // Excel row numbering (excluding header)
            foreach ($rows as $row) {
                $rowNumber++;
                // Skip empty rows (all values are null or empty)
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

                // Add department_id to the data
                $data['department_id'] = $department->id;

                // Get user_id from jurist name
                if (isset($data['jurist']) && $data['jurist']) {
                    $user = User::query()->where('name', 'ILIKE', $data['jurist'])->first();

                    // If not found by name, try to find via email from Excel file
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
                    $data['assigned_to'] = $user->id;
                    unset($data['jurist']);
                }

                // Get petition_status_id from status
                if (isset($data['status']) && $data['status']) {
                    // Map Excel status values to DB status values
                    $statusMapping = [
                        'afgedaan' => 'BOB verzonden',
                        'doorgezonden' => 'Bezwaar doorgezonden',
                        'in behandeling' => 'Wachten op gronden bezwaar',
                        'ingetrokken voor behandeling' => 'Bezwaar ingetrokken',
                    ];

                    $statusLower = strtolower((string) $data['status']);
                    $data['status'] = $statusMapping[$statusLower] ?? $data['status'];

                    $petitionStatus = PetitionStatus::query()
                        ->where('status', 'ILIKE', $data['status'])
                        ->first();
                    if (!$petitionStatus) {
                        $this->failedRecords[] = [
                            'row' => $rowNumber,
                            'reason' => sprintf('Status not found: %s', $data['status']),
                            'petition_number' => $data['number'],
                        ];
                        continue;
                    }
                    $data['petition_status_id'] = $petitionStatus->id;
                    unset($data['status']);
                }

                // Get petition_type_id from soort (for beroepen import) or default to 'Bezwaar'
                if ($useBeroepenMapping && isset($data['petition_type_id']) && $data['petition_type_id']) {
                    $petitionType = PetitionType::query()->where('name', $data['petition_type_id'])
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

                    // Map uitspraak to status for beroepen import
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

                    // If no status was set via 'uitspraak' mapping, check if 'zitting' has a date
                    if (!isset($data['petition_status_id']) && isset($data['zitting']) && $data['zitting']) {
                        $zittingStatus = PetitionStatus::query()
                            ->where('status', 'Zitting')
                            ->where('petition_type_id', $petitionType->id)
                            ->first();

                        if ($zittingStatus) {
                            $data['petition_status_id'] = $zittingStatus->id;
                        }
                    }

                    // If no status was set via uitspraak or 'zitting', get the first status for this petition type
                    if (!isset($data['petition_status_id'])) {
                        $firstStatus = PetitionStatus::query()
                            ->where('status', 'Toebedeling')
                            ->where('petition_type_id', $petitionType->id)
                            ->first();

                        if ($firstStatus) {
                            $data['petition_status_id'] = $firstStatus->id;
                        }
                    }
                } elseif (!$useBeroepenMapping) {
                    // Default to 'Bezwaar' for non-beroepen imports
                    $petitionType = PetitionType::query()->where('type', 'bezwaar')
                        ->where('department_id', $department->id)
                        ->first();
                    if ($petitionType) {
                        $data['petition_type_id'] = $petitionType->id;
                    }
                }

                // Get petition_category_id from categorie name
                if (isset($data['categorie']) && $data['categorie']) {
                    $categoryName = $data['categorie'];

                    // If ZAAKNUMMER is empty and category mapping exists, lookup the mapped category
                    if (isset($this->categoryMapping[$categoryName])) {
                        $categoryName = $this->categoryMapping[$categoryName];
                        $this->line(sprintf(
                            '  Category "%s" mapped to: %s',
                            $data['categorie'],
                            $categoryName,
                        ));
                    }

                    $petitionCategory = PetitionCategory::query()->where('name', $categoryName)
                        ->where('department_id', $department->id)
                        ->first();
                    if (!$petitionCategory) {
                        $this->failedRecords[] = [
                            'row' => $rowNumber,
                            'reason' => sprintf('Category not found: %s', $categoryName),
                            'petition_number' => $data['number'],
                        ];
                        continue;
                    }
                    $data['petition_category_id'] = $petitionCategory->id;
                    unset($data['categorie']);
                }

                // Find the petition data by number
                $petition = Petition::query()->where('number', $data['number'])->first();
                if (!$petition) {
                    // Create the petition record with the available data
                    // Only for the department "WJZ Afdeling Bezwaar en Beroep"
                    $petitionData = array_filter([
                        'number' => $data['number'],
                        'date_appealed_decision' => isset($data['date_appealed_decision']) ? $this->convertDateFormat(
                            $data['date_appealed_decision'],
                        ) : null,
                        'date_of_entry' => isset($data['date_of_entry']) ? $this->convertDateFormat(
                            $data['date_of_entry'],
                        ) : null,
                        'decision_reference' => $data['decision_reference'] ?? '',
                        'assigned_to' => $data['assigned_to'] ?? null,
                        'petition_status_id' => $data['petition_status_id'] ?? null,
                        'petition_category_id' => $data['petition_category_id'] ?? null,
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

                    if ($isDryRun) {
                        $this->line(sprintf(
                            'Would create petition: %s',
                            json_encode($petitionData),
                        ));
                        // Create a mock petition for dry-run validation
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
                    // Petition exists, update with Excel data
                    $petitionUpdateData = array_filter([
                        'date_appealed_decision' => isset($data['date_appealed_decision']) ? $this->convertDateFormat(
                            $data['date_appealed_decision'],
                        ) : null,
                        'date_of_entry' => isset($data['date_of_entry']) ? $this->convertDateFormat(
                            $data['date_of_entry'],
                        ) : null,
                        'decision_reference' => $data['decision_reference'] ?? null,
                        'assigned_to' => $data['assigned_to'] ?? null,
                        'petition_status_id' => $data['petition_status_id'] ?? null,
                        'petition_category_id' => $data['petition_category_id'] ?? null,
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

                // Handle Bezwaarde: get or create contact_petition_id
                if (isset($data['bezwaarde']) && $data['bezwaarde']) {
                    // 2. Get contact by last_name
                    $contact = Contact::query()->where('last_name', $data['bezwaarde'])
                        ->first();

                    // Create contact if not found
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
                        // 3. Check if contact_petition exists for this petition and contact with role='applicant'
                        $contactPetition = ContactPetition::query()->where('petition_id', $petition->id)
                            ->where('contact_id', $contact->id)
                            ->where('petition_id', $petition->id)
                            ->where('role', 'applicant')
                            ->first();

                        // If not exists, create new record
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
                                    'reference' => $data['decision_reference'] ?? '',
                                ]);
                                $this->importedIds['contact_petitions'][] = $created->id;
                            }
                        } elseif (!$isDryRun && isset($data['decision_reference'])) {
                            // If exists and has role='applicant', update the reference field
                            $contactPetition->update([
                                'reference' => $data['decision_reference'],
                            ]);
                            $this->line(sprintf(
                                '  Updated reference for contact "%s"',
                                $data['bezwaarde'],
                            ));
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

                // Handle Beleidsafdeling: get or create petition_policy_department
                if (isset($data['beleidsafdeling']) && $data['beleidsafdeling']) {
                    // Split by comma in case there are multiple policy departments
                    $policyDepartmentNames = array_map(trim(...), explode(',', (string) $data['beleidsafdeling']));
                    $policyDepartmentNames = array_filter($policyDepartmentNames, static fn ($name): bool => $name !== '');

                    foreach ($policyDepartmentNames as $policyDepartmentName) {
                        // 2. Get policy_department by name
                        $policyDepartment = PolicyDepartment::query()->where('name', 'ILIKE', $policyDepartmentName)
                            ->first();

                        if ($policyDepartment) {
                            // 3. Check if relationship already exists, if not attach it
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

                // Handle Zwaarte
                if (isset($data['zwaarte']) && $data['zwaarte']) {
                    $zwaarteProperty = CustomPetitionProperty::query()
                        ->where('petition_type_id', $petition->petition_type_id)
                        ->where('type', 'option')
                        ->where('name', 'ILIKE', $data['zwaarte'])
                        ->first();

                    if ($zwaarteProperty) {
                        if (!$isDryRun) {
                            // Delete existing records with the same grouping
                            DB::table('custom_petition_property_petition')
                                ->where('petition_id', $petition->id)
                                ->whereIn('custom_petition_property_id', static function ($query) use ($zwaarteProperty): void {
                                    $query->select('id')
                                        ->from('custom_petition_properties')
                                        ->where('grouping', $zwaarteProperty->grouping);
                                })
                                ->delete();

                            // Insert the new record
                            DB::table('custom_petition_property_petition')->insert([
                                'petition_id' => $petition->id,
                                'custom_petition_property_id' => $zwaarteProperty->id,
                            ]);
                        } else {
                            $this->line(sprintf('  Would add Zwaarte: %s', $data['zwaarte']));
                        }
                    }

                    unset($data['zwaarte']);
                }

                // Update petition_type_id if it exists in data (for existing petitions in beroepen import)
                if (isset($data['petition_type_id']) && $data['petition_type_id']) {
                    if (!$isDryRun && $petition->petition_type_id !== $data['petition_type_id']) { // @codeCoverageIgnore
                        $petition->update(['petition_type_id' => $data['petition_type_id']]); // @codeCoverageIgnore
                        $this->line(sprintf('  Updated petition_type_id for petition %s', $petition->number)); // @codeCoverageIgnore
                    } elseif ($isDryRun) {
                        $this->line(sprintf('  Would update petition_type_id for petition %s', $data['number']));
                    }
                    unset($data['petition_type_id']);
                }

                // Handle Dictum BOB
                if (isset($data['dictumbob']) && $data['dictumbob']) {
                    $dictumBobProperty = CustomPetitionProperty::query()
                        ->where('petition_type_id', $petition->petition_type_id)
                        ->where('type', 'option')
                        ->where('name', 'ILIKE', $data['dictumbob'])
                        ->first();

                    if ($dictumBobProperty) {
                        if (!$isDryRun) {
                            $propertyExists = DB::table('custom_petition_property_petition')
                                ->where('petition_id', $petition->id)
                                ->where('custom_petition_property_id', $dictumBobProperty->id)
                                ->exists();
                            if (!$propertyExists) {
                                DB::table('custom_petition_property_petition')->insert([
                                    'petition_id' => $petition->id,
                                    'custom_petition_property_id' => $dictumBobProperty->id,
                                ]);
                            }
                        } else {
                            $this->line(sprintf('  Would add Dictum BOB: %s', $data['dictumbob']));
                        }
                    }

                    unset($data['dictumbob']);
                }

                // Handle Reden intrekking
                $bezwarenMapping = [
                    'overig' => 'Overig',
                    'informeel' => 'Informeel',
                    'herziening' => '2021-2024: Herziening – herstel bezwaar, 2025: warning',
                    'anders' => 'Overig',
                ];

                $beroepenMapping = [
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

                // For beroepen: if uitspraak exists, map it to redenintrekking
                if ($useBeroepenMapping && isset($data['uitspraak']) && $data['uitspraak']) {
                    $uitspraakLower = strtolower((string) $data['uitspraak']);
                    $data['redenintrekking'] = $beroepenMapping[$uitspraakLower] ?? $data['uitspraak'];
                }

                if (isset($data['redenintrekking']) && $data['redenintrekking']) {
                    if (!$useBeroepenMapping) {
                        $data['redenintrekking'] = $bezwarenMapping[strtolower(
                            (string) $data['redenintrekking'],
                        )] ?? $data['redenintrekking'];
                    }

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

                // Handle Datum BOB, Datum Intrekking, Datum Doorzending
                $dateMapping = [
                    'datumbob' => CustomDateLabel::DATE_DECISION_ON_APPEAL,
                    'datumintrekking' => CustomDateLabel::DATE_WITHDRAWN,
                    'datumdoorzending' => CustomDateLabel::DATE_OF_FORWARDING,
                ];

                $beroepenDateMapping = [
                    'datum_uitspraak' => CustomDateLabel::DATE_RULING,
                    'zitting' => CustomDateLabel::DATE_COURT_SESSION,
                ];

                // Use beroepen date mapping if --beroepen flag is set
                $activeDateMapping = $useBeroepenMapping ? $beroepenDateMapping : $dateMapping;

                foreach ($activeDateMapping as $excelColumn => $dateLabel) {
                    if (isset($data[$excelColumn]) && $data[$excelColumn]) {
                        // Convert date from dd-mm-yyyy to yyyy-mm-dd
                        $formattedDate = $this->convertDateFormat($data[$excelColumn]);

                        // Check if this custom date already exists for this petition
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

            // Store batch info for rollback
            if (!$isDryRun && $insertedCount > 0) {
                Cache::put($this->batchId, $this->importedIds, now()->addDays(30));
                $this->info(sprintf('Batch ID: %s (stored for 30 days)', $this->batchId));
            }

            // Summary
            if ($isDryRun) {
                $this->info(sprintf(
                    'Dry run completed. Would import %d new petitions',
                    $insertedCount,
                ));
            } else {
                $this->info(sprintf('Successfully imported %d rows into petition table', $insertedCount));
            }

            // Display failed records
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

    /**
     * Rollback an import by batch ID.
     */
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

            // Delete petition custom dates
            if (isset($importedIds['petition_custom_dates'])) {
                $count = PetitionCustomDate::query()->whereIn('id', $importedIds['petition_custom_dates'])
                    ->delete();
                $deletedCount += $count;
                $this->info(sprintf('Deleted %d petition custom dates', $count));
            }

            // Delete contact petitions
            if (isset($importedIds['contact_petitions'])) {
                $count = ContactPetition::query()->whereIn('id', $importedIds['contact_petitions'])
                    ->delete();
                $deletedCount += $count;
                $this->info(sprintf('Deleted %d contact petitions', $count));
            }

            // Delete contacts
            if (isset($importedIds['contacts'])) {
                $count = Contact::query()->whereIn('id', $importedIds['contacts'])
                    ->delete();
                $deletedCount += $count;
                $this->info(sprintf('Deleted %d contacts', $count));
            }

            // Detach policy departments and custom properties
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

            // Delete petitions
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

    /**
     * Transform Excel column name to database field name using the mapping.
     */
    protected function transformColumnName(string $excelColumn, bool $useBeroepenMapping = false): string
    {
        $normalized = strtolower(trim($excelColumn));

        // Use beroepen mapping if flag is set
        $mapping = $useBeroepenMapping ? $this->beroepenColumnMapping : $this->columnMapping;

        // Check if mapping exists
        if (isset($mapping[$normalized])) {
            return $mapping[$normalized];
        }

        // Convert to snake_case
        return strtolower(str_replace([' ', '.'], '_', $excelColumn));
    }

    /**
     * Convert date from dd-mm-yyyy to yyyy-mm-dd format.
     */
    protected function convertDateFormat(string|int $date): ?string
    {
        if (in_array($date, ['', '0', 0], true)) {
            return null;
        }

        // If date is an integer (Excel numeric date), convert it
        if (is_int($date)) {
            try {
                // Excel dates are stored as days since 1900-01-01 (with a 1900 leap year bug)
                $unixTimestamp = ($date - 25_569) * 86_400;
                return date('Y-m-d', $unixTimestamp);
            } catch (Throwable) { // @codeCoverageIgnore
                return null; // @codeCoverageIgnore
            } // @codeCoverageIgnore
        }

        // Try 'j-n-Y' format first (e.g., '18-1-2000')
        try {
            $calendarDate = CalendarDate::createFromFormat('j-n-Y', $date);

            return $calendarDate->format('Y-m-d');
        } catch (Throwable) {
            // If first format fails, try 'n/j/y' format (e.g., '1/18/23')
            try {
                $calendarDate = CalendarDate::createFromFormat('n/j/y', $date);

                return $calendarDate->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }
    }

    /**
     * Load jurist name to email mapping from Excel file.
     */
    private function loadJuristEmailMapping(string $filePath): void
    {
        try {
            $rawData = Excel::toArray([], $filePath);
            $sheetData = $rawData[0] ?? [];

            if (count($sheetData) === 0) {
                $this->warn('Jurist Excel file is empty');
                return;
            }

            // Assume first row is header, expecting columns: name, email
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

    /**
     * Load category name mapping from Excel file.
     * Expects columns: CATEGORIE_IN_SHEET, CATEGORIE
     * Used when ZAAKNUMMER is empty or null.
     */
    private function loadCategoryMapping(string $filePath): void
    {
        try {
            $rawData = Excel::toArray([], $filePath);
            $sheetData = $rawData[0] ?? [];

            if (count($sheetData) === 0) {
                $this->warn('Category Excel file is empty');
                return;
            }

            // Assume first row is header, expecting columns: CATEGORIE_IN_SHEET, CATEGORIE
            $headers = array_map(
                static fn ($header) => strtolower(trim((string) $header)),
                $sheetData[0],
            );

            $categorieInSheetIndex = array_search('categorie_in_sheet', $headers, true);
            $categorieIndex = array_search('categorie', $headers, true);

            if ($categorieInSheetIndex === false || $categorieIndex === false) {
                $this->warn('Category file must contain "CATEGORIE_IN_SHEET" and "CATEGORIE" columns');
                return;
            }

            $rows = array_slice($sheetData, 1);
            foreach ($rows as $row) {
                $categorieInSheet = $row[$categorieInSheetIndex] ?? null;
                $categorie = $row[$categorieIndex] ?? null;

                if ($categorieInSheet && $categorie) {
                    $this->categoryMapping[(string) $categorieInSheet] = (string) $categorie;
                }
            }

            $this->info(sprintf(
                'Loaded %d category mappings from file',
                count($this->categoryMapping),
            ));
        } catch (Throwable $e) {
            $this->warn(sprintf('Failed to load category mapping: %s', $e->getMessage()));
        }
    }
}
