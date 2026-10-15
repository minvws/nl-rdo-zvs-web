<?php

declare(strict_types=1);

namespace App\Console\Commands\Petition;

use App\Actions\Petition\PetitionContactAttachAction;
use App\Actions\Petition\PetitionEventCreation\PetitionEventCreationStrategyResolver;
use App\Actions\Petition\PetitionPolicyDepartmentUpdateAction;
use App\Actions\PetitionEvent\UpdatePetitionTotalsFromEventsAction;
use App\Enums\AssignmentRole;
use App\Enums\ContactRole;
use App\Enums\ContactType;
use App\Enums\CustomPetitionPropertyType;
use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Enums\ResultType;
use App\Enums\TimelineType;
use App\Imports\RawSheetImport;
use App\Models\Contact;
use App\Models\CustomPetitionProperty;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionAssignment;
use App\Models\PetitionCategory;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use App\Models\PetitionType;
use App\Models\PolicyDepartment;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function array_search;
use function array_slice;
use function count;
use function explode;
use function file_exists;
use function implode;
use function is_numeric;
use function is_scalar;
use function round;
use function sprintf;
use function strtolower;
use function trim;

#[Description('Import an Excel file with Woo objections (bezwaren) into a team')]
#[Signature('petitions:import-woo-objections
    {file : Path to the Excel file with Woo objections (bezwaren)}
    {department : Slug of the team to import into}
    {--file-jurist= : Excel file mapping jurist names to email addresses (columns "name" and "email")}
    {--commit : Commit changes to database (default is dry-run)}')]
class ImportWooObjectionsCommand extends Command
{
    /**
     * Excel column header (lowercased) => field name used in this command.
     */
    private const array COLUMNS = [
        'zaaknummers' => 'number',
        'datum primair besluit' => 'date_appealed_decision',
        'kenmerk primair besluit' => 'decision_reference',
        'ontvangstdatum bezwaar' => 'date_of_entry',
        'jurist' => 'jurist',
        'bezwaarde' => 'bezwaarde',
        'categorie' => 'category',
        'beleidsafdeling' => 'policy_departments',
        'status' => 'status',
        'datum bob' => 'date_decision',
        'dictum bob' => 'dictum',
        'datum intrekking' => 'date_withdrawn',
        'reden intrekking' => 'withdrawal_reason',
        'datum doorzending' => 'date_forwarded',
        'zwaarte' => 'weight',
    ];

    private const array REQUIRED_COLUMNS = [
        'zaaknummers',
        'datum primair besluit',
        'ontvangstdatum bezwaar',
        'jurist',
        'categorie',
        'status',
    ];

    /**
     * Column with a closing date => result type of the "FINAL_RESULT" event it becomes.
     */
    private const array FINAL_RESULT_COLUMNS = [
        'date_decision' => ResultType::FINAL_DECISION,
        'date_withdrawn' => ResultType::WITHDRAWN,
        'date_forwarded' => ResultType::FORWARDED,
    ];

    /** @var array<string, string> Lowercased jurist name => email address */
    private array $juristEmails = [];

    /** @var list<string> */
    private array $imported = [];

    /** @var list<string> */
    private array $skipped = [];

    /** @var list<array{row: int, number: string, reason: string}> */
    private array $failed = [];

    public function __construct(
        private readonly PetitionEventCreationStrategyResolver $petitionEventCreationStrategyResolver,
        private readonly UpdatePetitionTotalsFromEventsAction $updatePetitionTotalsFromEventsAction,
        private readonly PetitionContactAttachAction $petitionContactAttachAction,
        private readonly PetitionPolicyDepartmentUpdateAction $petitionPolicyDepartmentUpdateAction,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        /** @var string $filePath */
        $filePath = $this->argument('file');
        /** @var string $slug */
        $slug = $this->argument('department');
        $isDryRun = !$this->option('commit');

        if (!file_exists($filePath)) {
            $this->error(sprintf('File not found: %s', $filePath));

            return self::FAILURE;
        }

        $department = Department::query()->where('slug', $slug)->first();
        if (!$department) {
            $this->error(sprintf('Department "%s" not found', $slug));

            return self::FAILURE;
        }

        $petitionType = PetitionType::query()
            ->where('department_id', $department->id)
            ->where('type', PetitionVariant::BEZWAAR->value)
            ->first();
        if (!$petitionType) {
            $this->error(sprintf('Department "%s" has no petition type of type "bezwaar"', $department->name));

            return self::FAILURE;
        }

        /** @var ?string $juristFilePath */
        $juristFilePath = $this->option('file-jurist');
        if ($juristFilePath !== null && !$this->loadJuristEmails($juristFilePath)) {
            return self::FAILURE;
        }

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to the database');
        }

        try {
            $sheet = Excel::toArray(new RawSheetImport(), $filePath)[0] ?? [];
        } catch (Throwable $throwable) {
            $this->error(sprintf('Error reading Excel file: %s', $throwable->getMessage()));

            return self::FAILURE;
        }

        if ($sheet === []) {
            $this->error('Excel file is empty');

            return self::FAILURE;
        }

        $headers = $this->mapHeaders($sheet[0]);
        $missingColumns = array_diff(self::REQUIRED_COLUMNS, array_keys($headers));
        if ($missingColumns !== []) {
            $this->error(sprintf('Missing column(s) in Excel file: %s', implode(', ', $missingColumns)));

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Importing into department "%s" as petition type "%s"',
            $department->name,
            $petitionType->name,
        ));

        DB::beginTransaction();

        try {
            foreach (array_slice($sheet, 1) as $index => $row) {
                $rowNumber = $index + 2;
                $data = $this->mapRow($headers, $row);

                if (array_filter($data) === []) {
                    continue;
                }

                $number = $data['number'];
                if ($number === '') {
                    $this->failed[] = ['row' => $rowNumber, 'number' => '', 'reason' => 'Missing zaaknummer'];
                    continue;
                }

                if (Petition::query()->where('number', $number)->exists()) {
                    $this->skipped[] = $number;
                    $this->line(sprintf('  Row %d: %s already exists, skipped', $rowNumber, $number));
                    continue;
                }

                try {
                    DB::transaction(function () use ($data, $department, $petitionType, $rowNumber): void {
                        $this->importRow($data, $department, $petitionType, $rowNumber);
                    });
                    $this->imported[] = $number;
                } catch (RuntimeException $runtimeException) {
                    $this->failed[] = ['row' => $rowNumber, 'number' => $number, 'reason' => $runtimeException->getMessage()];
                    $this->error(sprintf('  Row %d: %s failed: %s', $rowNumber, $number, $runtimeException->getMessage()));
                }
            }
        } catch (Throwable $throwable) {
            DB::rollBack();
            $this->error(sprintf('Import aborted, all changes rolled back: %s', $throwable->getMessage()));

            return self::FAILURE;
        }

        if ($isDryRun) {
            DB::rollBack();
        } else {
            DB::commit();
        }

        $this->printSummary($isDryRun);

        return self::SUCCESS;
    }

    private function loadJuristEmails(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            $this->error(sprintf('Jurist file not found: %s', $filePath));

            return false;
        }

        try {
            $sheet = Excel::toArray(new RawSheetImport(), $filePath)[0] ?? [];
        } catch (Throwable $throwable) {
            $this->error(sprintf('Error reading jurist file: %s', $throwable->getMessage()));

            return false;
        }

        $headers = array_map(
            static fn (mixed $header): string => strtolower(trim(is_scalar($header) ? (string) $header : '')),
            $sheet[0] ?? [],
        );
        $nameIndex = array_search('name', $headers, true);
        $emailIndex = array_search('email', $headers, true);
        if ($nameIndex === false || $emailIndex === false) {
            $this->error('Jurist file must contain "name" and "email" columns');

            return false;
        }

        foreach (array_slice($sheet, 1) as $row) {
            $name = $row[$nameIndex] ?? null;
            $email = $row[$emailIndex] ?? null;
            if (!is_scalar($name) || !is_scalar($email)) {
                continue;
            }

            $name = strtolower(trim((string) $name));
            $email = trim((string) $email);
            if ($name !== '' && $email !== '') {
                $this->juristEmails[$name] = $email;
            }
        }

        $this->info(sprintf('Loaded %d jurist email mapping(s) from %s', count($this->juristEmails), $filePath));

        return true;
    }

    /**
     * @param array<mixed> $headerRow
     *
     * @return array<string, int|string> Excel column header => column key
     */
    private function mapHeaders(array $headerRow): array
    {
        $headers = [];
        foreach ($headerRow as $index => $header) {
            $normalized = strtolower(trim(is_scalar($header) ? (string) $header : ''));
            if (isset(self::COLUMNS[$normalized])) {
                $headers[$normalized] = $index;
            }
        }

        return $headers;
    }

    /**
     * @param array<string, int|string> $headers
     * @param array<mixed> $row
     *
     * @return array<string, string> Field name => trimmed cell value, '' when the column is absent or empty
     */
    private function mapRow(array $headers, array $row): array
    {
        $data = [];
        foreach (self::COLUMNS as $column => $field) {
            $value = isset($headers[$column]) ? $row[$headers[$column]] ?? null : null;
            $data[$field] = is_scalar($value) ? trim((string) $value) : '';
        }

        return $data;
    }

    /**
     * @param array<string, string> $data
     */
    private function importRow(array $data, Department $department, PetitionType $petitionType, int $rowNumber): void
    {
        $user = $this->resolveUser($data['jurist']);
        $status = $this->resolveStatus($data['status'], $petitionType);
        $category = $this->resolveCategory($data['category'], $department);
        $policyDepartments = $this->resolvePolicyDepartments($data['policy_departments']);
        $dateOfEntry = $this->requireDate('ontvangstdatum bezwaar', $data['date_of_entry']);
        $dateAppealedDecision = $this->requireDate('datum primair besluit', $data['date_appealed_decision']);
        $finalResult = $this->resolveFinalResult($data);
        $properties = $this->resolveProperties($petitionType, $data, $rowNumber);

        $statusDate = $finalResult['date'] ?? $dateOfEntry;

        $petition = Petition::query()->create([
            'number' => $data['number'],
            'name' => $category->name,
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $status->id,
            'petition_category_id' => $category->id,
            'date_of_entry' => $dateOfEntry->toDateString(),
            'date_appealed_decision' => $dateAppealedDecision->toDateString(),
            'decision_reference' => $data['decision_reference'] === '' ? null : $data['decision_reference'],
        ]);

        $petition->timelineItems()->create([
            'user_id' => $user->id,
            'type' => TimelineType::TIMELINEABLE_CREATED,
        ]);

        PetitionStatusHistory::query()->create([
            'petition_id' => $petition->id,
            'petition_status_id' => $status->id,
            'date' => $statusDate->toDateString(),
        ]);
        $petition->timelineItems()->create([
            'user_id' => $user->id,
            'type' => TimelineType::STATUS_OCCURRENCE,
            'data' => new ArrayObject([
                'previous_status' => null,
                'current_status' => $status->status,
                'date' => $statusDate->toDateString(),
            ]),
        ]);

        $this->petitionEventCreationStrategyResolver
            ->resolve(PetitionVariant::BEZWAAR)
            ->create($petition, [
                'date_of_entry' => $dateOfEntry->toDateString(),
                'date_appealed_decision' => $dateAppealedDecision->toDateString(),
            ], $user);

        if ($finalResult !== null) {
            $eventData = new PetitionEventData(
                type: PetitionEventType::FINAL_RESULT,
                date: $finalResult['date'],
                createdAt: CarbonImmutable::now(),
                resultType: $finalResult['result_type'],
            );
            $petition->petitionEvents()->create($eventData->toArray());
            $petition->refresh();
            $this->updatePetitionTotalsFromEventsAction->execute($petition);
        }

        PetitionAssignment::query()->create([
            'petition_id' => $petition->id,
            'user_id' => $user->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        if ($data['bezwaarde'] !== '') {
            $contact = $this->resolveContact($data['bezwaarde'], $department);
            $this->petitionContactAttachAction->execute(ContactRole::APPLICANT, $petition, $contact, $user);
        }

        if ($policyDepartments !== []) {
            $this->petitionPolicyDepartmentUpdateAction->execute($petition, $user, [
                'policy_department_ids' => array_map(
                    static fn (PolicyDepartment $policyDepartment): string => $policyDepartment->id->toString(),
                    $policyDepartments,
                ),
            ]);
        }

        if ($properties !== []) {
            $petition->customPetitionProperties()->attach(
                array_map(static fn (CustomPetitionProperty $property): string => $property->id->toString(), $properties),
            );
        }

        $this->line(sprintf(
            '  Row %d: %s - %s, %s, jurist %s, beleidsafdeling %s, bezwaarde %s, %s, properties %s',
            $rowNumber,
            $petition->number,
            $category->name,
            $status->status,
            $user->name,
            $policyDepartments === []
                ? '-'
                : implode(
                    ', ',
                    array_map(static fn (PolicyDepartment $policyDepartment): string => $policyDepartment->name, $policyDepartments),
                ),
            $data['bezwaarde'] === '' ? '-' : $data['bezwaarde'],
            $finalResult === null
                ? 'no final result'
                : sprintf('%s on %s', $finalResult['result_type']->value, $finalResult['date']->toDateString()),
            $properties === []
                ? '-'
                : implode(', ', array_map(static fn (CustomPetitionProperty $property): string => $property->name, $properties)),
        ));
    }

    private function resolveUser(string $name): User
    {
        if ($name === '') {
            throw new RuntimeException('Missing jurist');
        }

        $user = User::query()->where('name', 'ILIKE', $name)->first();
        if ($user) {
            return $user;
        }

        $email = $this->juristEmails[strtolower($name)] ?? null;
        if ($email === null) {
            throw new RuntimeException(sprintf('Jurist not found: %s', $name));
        }

        $user = User::query()->where('email', 'ILIKE', $email)->first();
        if (!$user) {
            throw new RuntimeException(sprintf('Jurist not found: %s (mapped to %s)', $name, $email));
        }

        return $user;
    }

    private function resolveStatus(string $status, PetitionType $petitionType): PetitionStatus
    {
        if ($status === '') {
            throw new RuntimeException('Missing status');
        }

        $petitionStatus = PetitionStatus::query()
            ->where('petition_type_id', $petitionType->id)
            ->where('status', 'ILIKE', $status)
            ->first();
        if (!$petitionStatus) {
            throw new RuntimeException(sprintf('Status not found for petition type "%s": %s', $petitionType->name, $status));
        }

        return $petitionStatus;
    }

    private function resolveCategory(string $name, Department $department): PetitionCategory
    {
        if ($name === '') {
            throw new RuntimeException('Missing categorie');
        }

        $category = PetitionCategory::query()
            ->where('department_id', $department->id)
            ->where('name', 'ILIKE', $name)
            ->first();
        if (!$category) {
            throw new RuntimeException(sprintf('Category not found for department "%s": %s', $department->name, $name));
        }

        return $category;
    }

    /**
     * @return list<PolicyDepartment>
     */
    private function resolvePolicyDepartments(string $names): array
    {
        $policyDepartments = [];
        foreach (array_filter(array_map(trim(...), explode(',', $names))) as $name) {
            $policyDepartment = PolicyDepartment::query()->where('name', 'ILIKE', $name)->first();
            if (!$policyDepartment) {
                throw new RuntimeException(sprintf('Policy department not found: %s', $name));
            }

            $policyDepartments[] = $policyDepartment;
        }

        return $policyDepartments;
    }

    private function resolveContact(string $lastName, Department $department): Contact
    {
        return Contact::query()
            ->where('department_id', $department->id)
            ->where('last_name', 'ILIKE', $lastName)
            ->first() ?? Contact::query()->create([
                'last_name' => $lastName,
                'department_id' => $department->id,
                'type' => ContactType::CIVILIAN,
            ]);
    }

    /**
     * @param array<string, string> $data
     *
     * @return array{result_type: ResultType, date: CalendarDate}|null
     */
    private function resolveFinalResult(array $data): ?array
    {
        $finalResults = [];
        foreach (self::FINAL_RESULT_COLUMNS as $field => $resultType) {
            $date = $this->parseDate($field, $data[$field]);
            if ($date instanceof CalendarDate) {
                $finalResults[] = ['result_type' => $resultType, 'date' => $date];
            }
        }

        if (count($finalResults) > 1) {
            throw new RuntimeException('More than one of datum bob, datum intrekking and datum doorzending is filled');
        }

        return $finalResults[0] ?? null;
    }

    /**
     * @param array<string, string> $data
     *
     * @return list<CustomPetitionProperty>
     */
    private function resolveProperties(PetitionType $petitionType, array $data, int $rowNumber): array
    {
        $properties = [];
        foreach (['dictum', 'withdrawal_reason', 'weight'] as $field) {
            if ($data[$field] === '') {
                continue;
            }

            $property = CustomPetitionProperty::query()
                ->where('petition_type_id', $petitionType->id)
                ->where('type', CustomPetitionPropertyType::OPTION)
                ->where('name', 'ILIKE', $data[$field])
                ->first();
            if (!$property) {
                $this->warn(sprintf(
                    '  Row %d: custom property option "%s" not found for petition type "%s", skipped',
                    $rowNumber,
                    $data[$field],
                    $petitionType->name,
                ));
                continue;
            }

            $properties[] = $property;
        }

        return $properties;
    }

    private function requireDate(string $column, string $value): CalendarDate
    {
        $date = $this->parseDate($column, $value);
        if (!$date instanceof CalendarDate) {
            throw new RuntimeException(sprintf('Missing %s', $column));
        }

        return $date;
    }

    private function parseDate(string $column, string $value): ?CalendarDate
    {
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            // Excel serial date
            return CalendarDate::instance(CarbonImmutable::createFromTimestampUTC((int) round(((float) $value - 25_569) * 86_400)));
        }

        try {
            return CalendarDate::createFromFormat('j-n-Y', $value);
        } catch (Throwable) {
            throw new RuntimeException(sprintf('Invalid date for %s: %s', $column, $value));
        }
    }

    private function printSummary(bool $isDryRun): void
    {
        $this->newLine();
        $this->info($isDryRun ? 'Dry run completed, all changes rolled back' : 'Import completed');
        $this->info(sprintf('%s: %d', $isDryRun ? 'Would import' : 'Imported', count($this->imported)));

        $this->info(sprintf('Skipped because the zaaknummer already exists: %d', count($this->skipped)));
        foreach ($this->skipped as $number) {
            $this->line(sprintf('  %s', $number));
        }

        if ($this->failed === []) {
            return;
        }

        $this->warn(sprintf('Failed: %d', count($this->failed)));
        foreach ($this->failed as $failed) {
            $this->line(sprintf('  Row %d (%s): %s', $failed['row'], $failed['number'], $failed['reason']));
        }
    }
}
