<?php

declare(strict_types=1);

namespace Tests\Feature\Commands\Petition;

use App\Actions\Petition\PetitionContactAttachAction;
use App\Enums\AssignmentRole;
use App\Enums\ContactRole;
use App\Enums\ContactType;
use App\Enums\CustomPetitionPropertyType;
use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Enums\ResultType;
use App\Enums\TimelineType;
use App\Models\Contact;
use App\Models\ContactPetition;
use App\Models\CustomPetitionProperty;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionAssignment;
use App\Models\PetitionCategory;
use App\Models\PetitionEvent;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use App\Models\PetitionType;
use App\Models\PolicyDepartment;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\PendingCommand;
use LogicException;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Feature\FeatureTestCase;

use function array_fill;
use function array_map;
use function array_merge;
use function array_values;
use function file_exists;
use function file_put_contents;
use function storage_path;
use function unlink;

class ImportWooObjectionsCommandTest extends FeatureTestCase
{
    private const string FILE = 'test-import-woo-objections.xlsx';
    private const string JURIST_FILE = 'test-import-woo-objections-jurist.xlsx';

    private const array HEADERS = [
        'Zaaknummers',
        'Datum Primair Besluit',
        'Kenmerk primair besluit',
        'Ontvangstdatum Bezwaar',
        'Jurist',
        'Bezwaarde',
        'Categorie ',
        'Beleidsafdeling',
        'Status',
        'Datum BOB',
        'Dictum BOB',
        'Datum intrekking',
        'Reden intrekking',
        'Datum Doorzending',
        'Zwaarte',
    ];

    private const array ROW = [
        'number' => '2023001275',
        'date_appealed_decision' => '1-11-2023',
        'decision_reference' => 'WJZ-2023.185',
        'date_of_entry' => '3-11-2023',
        'jurist' => 'Henk Giertank',
        'bezwaarde' => 'Klaas Vaak',
        'category' => 'Woo-verzoek',
        'policy_departments' => 'WJZ',
        'status' => 'BOB verzonden',
        'date_decision' => '1-8-2024',
        'dictum' => 'Ongegrond',
        'date_withdrawn' => null,
        'withdrawal_reason' => null,
        'date_forwarded' => null,
        'weight' => 'B',
    ];

    private Department $department;
    private PetitionType $petitionType;
    private PetitionStatus $decisionSentStatus;
    private PetitionCategory $category;
    private User $jurist;
    private PolicyDepartment $policyDepartment;
    private int $transactionLevelBeforeCommand = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create(['slug' => 'team-c', 'name' => 'Team C']);
        $this->petitionType = PetitionType::factory()
            ->recycle($this->department)
            ->create(['type' => PetitionVariant::BEZWAAR, 'name' => 'Bezwaar']);
        $this->decisionSentStatus = PetitionStatus::factory()
            ->recycle($this->petitionType)
            ->create(['status' => 'BOB verzonden']);
        PetitionStatus::factory()->recycle($this->petitionType)->create(['status' => 'Bezwaar ingetrokken']);
        $this->category = PetitionCategory::factory()
            ->recycle($this->department)
            ->create(['name' => 'Woo-verzoek']);
        $this->jurist = User::factory()->create(['name' => 'Henk Giertank']);
        $this->policyDepartment = PolicyDepartment::factory()->create(['name' => 'WJZ']);
        foreach (['Ongegrond', 'Informeel', 'B'] as $option) {
            CustomPetitionProperty::factory()
                ->recycle($this->petitionType)
                ->create(['name' => $option, 'type' => CustomPetitionPropertyType::OPTION]);
        }

        file_put_contents(storage_path('app/' . self::FILE), 'stub');
        file_put_contents(storage_path('app/' . self::JURIST_FILE), 'stub');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach ([self::FILE, self::JURIST_FILE] as $file) {
            if (file_exists(storage_path('app/' . $file))) {
                unlink(storage_path('app/' . $file));
            }
        }
    }

    // ─── Fatal errors before any row is read ────────────────────────────────

    public function testFileNotFound(): void
    {
        $this->artisan('petitions:import-woo-objections', ['file' => '/nonexistent.xlsx', 'department' => 'team-c'])
            ->expectsOutput('File not found: /nonexistent.xlsx')
            ->assertFailed();
    }

    public function testDepartmentNotFound(): void
    {
        $this->runImport([], department: 'nope')
            ->expectsOutput('Department "nope" not found')
            ->assertFailed();
    }

    public function testDepartmentWithoutObjectionType(): void
    {
        Department::factory()->create(['slug' => 'team-a', 'name' => 'Team A']);

        $this->runImport([], department: 'team-a')
            ->expectsOutput('Department "Team A" has no petition type of type "bezwaar"')
            ->assertFailed();
    }

    public function testExcelThrows(): void
    {
        Excel::shouldReceive('toArray')->andThrow(new Exception('corrupt file'));

        $this->artisan('petitions:import-woo-objections', ['file' => storage_path('app/' . self::FILE), 'department' => 'team-c'])
            ->expectsOutput('Error reading Excel file: corrupt file')
            ->assertFailed();
    }

    public function testEmptyExcel(): void
    {
        Excel::shouldReceive('toArray')->andReturn([[]]);

        $this->artisan('petitions:import-woo-objections', ['file' => storage_path('app/' . self::FILE), 'department' => 'team-c'])
            ->expectsOutput('Excel file is empty')
            ->assertFailed();
    }

    public function testMissingRequiredColumns(): void
    {
        Excel::shouldReceive('toArray')->andReturn([[['Zaaknummers', 'Jurist', null, 'Onbekend']]]);

        $this->artisan('petitions:import-woo-objections', ['file' => storage_path('app/' . self::FILE), 'department' => 'team-c'])
            ->expectsOutput('Missing column(s) in Excel file: datum primair besluit, ontvangstdatum bezwaar, categorie, status')
            ->assertFailed();
    }

    // ─── Dry run and commit ─────────────────────────────────────────────────

    public function testDryRunRollsBackEverything(): void
    {
        $this->runImport([self::ROW])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Importing into department "Team C" as petition type "Bezwaar"')
            ->expectsOutput(
                '  Row 2: 2023001275 - Woo-verzoek, BOB verzonden, jurist Henk Giertank, beleidsafdeling WJZ, bezwaarde Klaas Vaak, final_decision on 2024-08-01, properties Ongegrond, B',
            )
            ->expectsOutput('Dry run completed, all changes rolled back')
            ->expectsOutput('Would import: 1')
            ->expectsOutput('Skipped because the zaaknummer already exists: 0')
            ->assertSuccessful();

        $this->assertDatabaseMissing(Petition::class, ['number' => '2023001275']);
        $this->assertDatabaseMissing(Contact::class, ['last_name' => 'Klaas Vaak']);
        $this->assertSame(0, DB::transactionLevel() - $this->transactionLevelBeforeCommand);
    }

    public function testCommitImportsAFullRow(): void
    {
        $this->runImport([self::ROW], commit: true)
            ->doesntExpectOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Import completed')
            ->expectsOutput('Imported: 1')
            ->assertSuccessful();

        $petition = Petition::query()->where('number', '2023001275')->firstOrFail();
        $this->assertSame('Woo-verzoek', $petition->name);
        $this->assertSame('WJZ-2023.185', $petition->decision_reference);
        $this->assertSame('2023-11-03', $petition->date_of_entry->toDateString());
        $this->assertSame('2023-11-01', $petition->date_appealed_decision?->toDateString());
        $this->assertSame($this->department->id->toString(), (string) $petition->department_id);
        $this->assertSame($this->petitionType->id->toString(), (string) $petition->petition_type_id);
        $this->assertSame($this->decisionSentStatus->id->toString(), (string) $petition->petition_status_id);
        $this->assertSame($this->category->id->toString(), (string) $petition->petition_category_id);

        $this->assertDatabaseHas(
            PetitionEvent::class,
            ['petition_id' => $petition->id, 'type' => PetitionEventType::PRIMARY_DECISION->value, 'date' => '2023-11-01'],
        );
        $this->assertDatabaseHas(
            PetitionEvent::class,
            ['petition_id' => $petition->id, 'type' => PetitionEventType::RECEIPT_OF_OBJECTION->value, 'date' => '2023-11-03'],
        );
        $this->assertDatabaseHas(
            PetitionEvent::class,
            ['petition_id' => $petition->id, 'type' => PetitionEventType::FINAL_RESULT->value, 'result_type' => ResultType::FINAL_DECISION->value, 'date' => '2024-08-01'],
        );
        $this->assertSame(3, PetitionEvent::query()->where('petition_id', $petition->id)->count());
        $this->assertNotNull($petition->deadline_decision_period);

        $this->assertDatabaseHas(
            PetitionStatusHistory::class,
            ['petition_id' => $petition->id, 'petition_status_id' => $this->decisionSentStatus->id, 'date' => '2024-08-01'],
        );
        $this->assertDatabaseHas(
            PetitionAssignment::class,
            ['petition_id' => $petition->id, 'user_id' => $this->jurist->id, 'assignment_role' => AssignmentRole::PRIMARY->value],
        );

        $contact = Contact::query()->where('last_name', 'Klaas Vaak')->firstOrFail();
        $this->assertDatabaseHas(
            Contact::class,
            ['id' => $contact->id, 'department_id' => $this->department->id, 'type' => ContactType::CIVILIAN->value],
        );
        $this->assertDatabaseHas(
            ContactPetition::class,
            ['petition_id' => $petition->id, 'contact_id' => $contact->id, 'role' => ContactRole::APPLICANT->value],
        );

        $this->assertDatabaseHas(
            'petition_policy_department',
            ['petition_id' => $petition->id, 'policy_department_id' => $this->policyDepartment->id],
        );
        $this->assertSame(['B', 'Ongegrond'], $petition->customPetitionProperties()->orderBy('name')->pluck('name')->all());

        $this->assertDatabaseHas(
            'timeline_items',
            ['timelineable_id' => $petition->id, 'type' => TimelineType::TIMELINEABLE_CREATED->value, 'user_id' => $this->jurist->id],
        );
        $this->assertDatabaseHas('timeline_items', ['timelineable_id' => $petition->id, 'type' => TimelineType::STATUS_OCCURRENCE->value]);
    }

    public function testMinimalRowWithoutOptionalValues(): void
    {
        $row = array_merge(self::ROW, [
            'decision_reference' => null,
            'bezwaarde' => null,
            'policy_departments' => null,
            'date_decision' => null,
            'dictum' => null,
            'weight' => null,
        ]);

        $this->runImport([$row], commit: true)
            ->expectsOutput(
                '  Row 2: 2023001275 - Woo-verzoek, BOB verzonden, jurist Henk Giertank, beleidsafdeling -, bezwaarde -, no final result, properties -',
            )
            ->expectsOutput('Imported: 1')
            ->assertSuccessful();

        $petition = Petition::query()->where('number', '2023001275')->firstOrFail();
        $this->assertNull($petition->decision_reference);
        $this->assertSame(2, PetitionEvent::query()->where('petition_id', $petition->id)->count());
        $this->assertDatabaseHas(PetitionStatusHistory::class, ['petition_id' => $petition->id, 'date' => '2023-11-03']);
        $this->assertDatabaseMissing(ContactPetition::class, ['petition_id' => $petition->id]);
        $this->assertDatabaseMissing('petition_policy_department', ['petition_id' => $petition->id]);
        $this->assertSame(0, $petition->customPetitionProperties()->count());
    }

    public function testWithdrawnAndForwardedRows(): void
    {
        $withdrawn = array_merge(self::ROW, [
            'number' => '2023000052',
            'status' => 'Bezwaar ingetrokken',
            'date_decision' => null,
            'dictum' => null,
            'date_withdrawn' => '17-4-2023',
            'withdrawal_reason' => 'Informeel',
        ]);
        $forwarded = array_merge(self::ROW, [
            'number' => '2023000053',
            'date_decision' => null,
            'dictum' => null,
            'date_forwarded' => '20-3-2023',
        ]);

        $this->runImport([$withdrawn, $forwarded], commit: true)
            ->expectsOutputToContain(
                '2023000052 - Woo-verzoek, Bezwaar ingetrokken, jurist Henk Giertank, beleidsafdeling WJZ, bezwaarde Klaas Vaak, withdrawn on 2023-04-17, properties Informeel, B',
            )
            ->expectsOutputToContain(
                '2023000053 - Woo-verzoek, BOB verzonden, jurist Henk Giertank, beleidsafdeling WJZ, bezwaarde Klaas Vaak, forwarded on 2023-03-20, properties B',
            )
            ->expectsOutput('Imported: 2')
            ->assertSuccessful();

        $withdrawnPetition = Petition::query()->where('number', '2023000052')->firstOrFail();
        $this->assertDatabaseHas(
            PetitionEvent::class,
            ['petition_id' => $withdrawnPetition->id, 'type' => PetitionEventType::FINAL_RESULT->value, 'result_type' => ResultType::WITHDRAWN->value, 'date' => '2023-04-17'],
        );
        $this->assertDatabaseHas(PetitionStatusHistory::class, ['petition_id' => $withdrawnPetition->id, 'date' => '2023-04-17']);
        $forwardedPetition = Petition::query()->where('number', '2023000053')->firstOrFail();
        $this->assertDatabaseHas(
            PetitionEvent::class,
            ['petition_id' => $forwardedPetition->id, 'type' => PetitionEventType::FINAL_RESULT->value, 'result_type' => ResultType::FORWARDED->value, 'date' => '2023-03-20'],
        );
    }

    public function testReusesExistingContactAndPolicyDepartments(): void
    {
        $contact = Contact::factory()->recycle($this->department)->create(['last_name' => 'klaas vaak']);
        Contact::factory()->create(['last_name' => 'Klaas Vaak']); // other department, must not be used
        $pdo = PolicyDepartment::factory()->create(['name' => 'PDO']);

        $this->runImport([array_merge(self::ROW, ['policy_departments' => 'WJZ, pdo'])], commit: true)
            ->expectsOutputToContain('beleidsafdeling WJZ, PDO')
            ->assertSuccessful();

        $petition = Petition::query()->where('number', '2023001275')->firstOrFail();
        $this->assertSame(1, Contact::query()->where('department_id', $this->department->id)->count());
        $this->assertDatabaseHas(ContactPetition::class, ['petition_id' => $petition->id, 'contact_id' => $contact->id]);
        $this->assertSame(2, $petition->policyDepartments()->count());
        $this->assertDatabaseHas('petition_policy_department', ['petition_id' => $petition->id, 'policy_department_id' => $pdo->id]);
    }

    public function testParsesExcelSerialDates(): void
    {
        // 45231 = 2023-11-01, 45233 = 2023-11-03
        $this->runImport(
            [
                array_merge(
                    self::ROW,
                    ['date_appealed_decision' => 45_231, 'date_of_entry' => 45_233.0, 'date_decision' => null, 'dictum' => null],
                ),
            ],
            commit: true,
        )
            ->expectsOutput('Imported: 1')
            ->assertSuccessful();

        $petition = Petition::query()->where('number', '2023001275')->firstOrFail();
        $this->assertSame('2023-11-03', $petition->date_of_entry->toDateString());
        $this->assertSame('2023-11-01', $petition->date_appealed_decision?->toDateString());
    }

    // ─── Skipped rows ───────────────────────────────────────────────────────

    public function testSkipsEmptyRowsAndExistingNumbersAndReportsThem(): void
    {
        Petition::factory()->create(['number' => '2023001275']);
        $second = array_merge(self::ROW, ['number' => '2023001276']);

        $this->runImport([self::ROW, array_fill(0, 15, null), $second, ['', '', '']], commit: true)
            ->expectsOutput('  Row 2: 2023001275 already exists, skipped')
            ->expectsOutputToContain('  Row 4: 2023001276 - ')
            ->expectsOutput('Imported: 1')
            ->expectsOutput('Skipped because the zaaknummer already exists: 1')
            ->expectsOutput('  2023001275')
            ->assertSuccessful();

        $this->assertSame(1, Petition::query()->where('number', '2023001275')->count());
        $this->assertDatabaseHas(Petition::class, ['number' => '2023001276']);
    }

    // ─── Failed rows ────────────────────────────────────────────────────────

    public function testRowFailuresAreReportedAndRolledBackPerRow(): void
    {
        $rows = [
            array_merge(self::ROW, ['number' => null]),
            array_merge(self::ROW, ['number' => '2023000001', 'jurist' => null]),
            array_merge(self::ROW, ['number' => '2023000002', 'jurist' => 'Onbekend']),
            array_merge(self::ROW, ['number' => '2023000003', 'status' => null]),
            array_merge(self::ROW, ['number' => '2023000004', 'status' => 'Onbekend']),
            array_merge(self::ROW, ['number' => '2023000005', 'category' => null]),
            array_merge(self::ROW, ['number' => '2023000006', 'category' => 'Onbekend']),
            array_merge(self::ROW, ['number' => '2023000007', 'policy_departments' => 'WJZ, Onbekend']),
            array_merge(self::ROW, ['number' => '2023000008', 'date_of_entry' => null]),
            array_merge(self::ROW, ['number' => '2023000009', 'date_appealed_decision' => 'gisteren']),
            array_merge(self::ROW, ['number' => '2023000010', 'date_withdrawn' => '17-4-2023']),
            array_merge(self::ROW, ['number' => '2023000011']),
        ];

        $this->runImport($rows, commit: true)
            ->expectsOutput('  Row 3: 2023000001 failed: Missing jurist')
            ->expectsOutput('  Row 4: 2023000002 failed: Jurist not found: Onbekend')
            ->expectsOutput('  Row 5: 2023000003 failed: Missing status')
            ->expectsOutput('  Row 6: 2023000004 failed: Status not found for petition type "Bezwaar": Onbekend')
            ->expectsOutput('  Row 7: 2023000005 failed: Missing categorie')
            ->expectsOutput('  Row 8: 2023000006 failed: Category not found for department "Team C": Onbekend')
            ->expectsOutput('  Row 9: 2023000007 failed: Policy department not found: Onbekend')
            ->expectsOutput('  Row 10: 2023000008 failed: Missing ontvangstdatum bezwaar')
            ->expectsOutput('  Row 11: 2023000009 failed: Invalid date for datum primair besluit: gisteren')
            ->expectsOutput('  Row 12: 2023000010 failed: More than one of datum bob, datum intrekking and datum doorzending is filled')
            ->expectsOutput('Imported: 1')
            ->expectsOutput('Failed: 11')
            ->expectsOutput('  Row 2 (): Missing zaaknummer')
            ->expectsOutput('  Row 12 (2023000010): More than one of datum bob, datum intrekking and datum doorzending is filled')
            ->assertSuccessful();

        $this->assertSame(1, Petition::query()->where('number', 'like', '2023%')->count());
        $this->assertDatabaseHas(Petition::class, ['number' => '2023000011']);
    }

    public function testUnknownCustomPropertyOptionIsSkippedWithAWarning(): void
    {
        $this->runImport([array_merge(self::ROW, ['dictum' => 'Kennelijk gegrond'])], commit: true)
            ->expectsOutput('  Row 2: custom property option "Kennelijk gegrond" not found for petition type "Bezwaar", skipped')
            ->expectsOutputToContain('properties B')
            ->expectsOutput('Imported: 1')
            ->assertSuccessful();

        $petition = Petition::query()->where('number', '2023001275')->firstOrFail();
        $this->assertSame(['B'], $petition->customPetitionProperties()->pluck('name')->all());
    }

    public function testUnexpectedExceptionAbortsAndRollsBack(): void
    {
        $this->app->instance(PetitionContactAttachAction::class, new readonly class extends PetitionContactAttachAction {
            public function __construct()
            {
            }

            public function execute(ContactRole $role, Petition $petition, Contact $contact, User $user): void
            {
                throw new LogicException('boom');
            }
        });

        $this->runImport([self::ROW], commit: true)
            ->expectsOutput('Import aborted, all changes rolled back: boom')
            ->assertFailed();

        $this->assertDatabaseMissing(Petition::class, ['number' => '2023001275']);
        $this->assertSame(0, DB::transactionLevel() - $this->transactionLevelBeforeCommand);
    }

    // ─── Jurist mapping file ────────────────────────────────────────────────

    public function testJuristFileNotFound(): void
    {
        Excel::shouldReceive('toArray')->never();

        $this->artisan('petitions:import-woo-objections', [
            'file' => storage_path('app/' . self::FILE),
            'department' => 'team-c',
            '--file-jurist' => '/nonexistent-jurist.xlsx',
        ])
            ->expectsOutput('Jurist file not found: /nonexistent-jurist.xlsx')
            ->assertFailed();
    }

    public function testJuristFileThrows(): void
    {
        Excel::shouldReceive('toArray')->andThrow(new Exception('corrupt file'));

        $this->artisan('petitions:import-woo-objections', [
            'file' => storage_path('app/' . self::FILE),
            'department' => 'team-c',
            '--file-jurist' => storage_path('app/' . self::JURIST_FILE),
        ])
            ->expectsOutput('Error reading jurist file: corrupt file')
            ->assertFailed();
    }

    public function testJuristFileWithoutNameAndEmailColumns(): void
    {
        Excel::shouldReceive('toArray')->andReturn([[['Naam', 'E-mail'], ['Henk', 'henk@example.org']]]);

        $this->artisan('petitions:import-woo-objections', [
            'file' => storage_path('app/' . self::FILE),
            'department' => 'team-c',
            '--file-jurist' => storage_path('app/' . self::JURIST_FILE),
        ])
            ->expectsOutput('Jurist file must contain "name" and "email" columns')
            ->assertFailed();
    }

    public function testJuristIsFoundThroughTheMappingFile(): void
    {
        $mapped = User::factory()->create(['name' => 'H. Giertank', 'email' => 'giertank@example.org']);

        $this->runImport(
            [
                array_merge(self::ROW, ['jurist' => 'Henk Giertank']),
                array_merge(self::ROW, ['number' => '2023001276', 'jurist' => 'giertank, henk']),
                array_merge(self::ROW, ['number' => '2023001277', 'jurist' => 'Piet Puk']),
                array_merge(self::ROW, ['number' => '2023001278', 'jurist' => 'Jan Jansen']),
            ],
            commit: true,
            juristRows: [
                ['Email', ' Name ', null],
                ['GIERTANK@example.org', 'Giertank, Henk', 'ignored'],
                ['nobody@example.org', 'Piet Puk'],
                [null, 'Klaas'],
                ['klaas@example.org', ''],
                [['not'], 'scalar'],
            ],
        )
            ->expectsOutput('Loaded 2 jurist email mapping(s) from ' . storage_path('app/' . self::JURIST_FILE))
            ->expectsOutputToContain('2023001275 - Woo-verzoek, BOB verzonden, jurist Henk Giertank,')
            ->expectsOutputToContain('2023001276 - Woo-verzoek, BOB verzonden, jurist H. Giertank,')
            ->expectsOutput('  Row 4: 2023001277 failed: Jurist not found: Piet Puk (mapped to nobody@example.org)')
            ->expectsOutput('  Row 5: 2023001278 failed: Jurist not found: Jan Jansen')
            ->expectsOutput('Imported: 2')
            ->assertSuccessful();

        $petition = Petition::query()->where('number', '2023001276')->firstOrFail();
        $this->assertDatabaseHas(PetitionAssignment::class, ['petition_id' => $petition->id, 'user_id' => $mapped->id]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /**
     * @param array<int, array<array-key, scalar|null>> $rows Rows keyed like self::ROW (or plain lists)
     * @param array<int, array<int, mixed>>|null $juristRows Rows of the jurist mapping file, header row first
     */
    private function runImport(array $rows, bool $commit = false, string $department = 'team-c', ?array $juristRows = null): PendingCommand
    {
        $filePath = storage_path('app/' . self::FILE);
        $juristFilePath = storage_path('app/' . self::JURIST_FILE);

        Excel::shouldReceive('toArray')
            ->withArgs(static fn (mixed $import, string $path): bool => $path === $filePath)
            ->andReturn([
                [
                    self::HEADERS,
                    ...array_map(array_values(...), $rows),
                ],
            ]);
        if ($juristRows !== null) {
            Excel::shouldReceive('toArray')
                ->withArgs(static fn (mixed $import, string $path): bool => $path === $juristFilePath)
                ->andReturn([$juristRows]);
        }

        $this->transactionLevelBeforeCommand = DB::transactionLevel();

        return $this->artisan('petitions:import-woo-objections', [
            'file' => $filePath,
            'department' => $department,
            '--file-jurist' => $juristRows === null ? null : $juristFilePath,
            '--commit' => $commit,
        ]);
    }
}
