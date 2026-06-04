<?php

declare(strict_types=1);

namespace Tests\Feature\Commands\Petition;

use App\Enums\AssignmentRole;
use App\Enums\CustomDateLabel;
use App\Enums\CustomPetitionPropertyType;
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
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Feature\FeatureTestCase;

use function file_exists;
use function file_put_contents;
use function now;
use function storage_path;
use function unlink;

class ImportAppealsCommandTest extends FeatureTestCase
{
    protected string $testFilePath = 'test-import-appeals.xlsx';
    protected string $testJuristFilePath = 'test-import-appeals-jurist.xlsx';

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach ([$this->testFilePath, $this->testJuristFilePath] as $path) {
            $full = storage_path('app/' . $path);
            if (file_exists($full)) {
                unlink($full);
            }
        }
    }

    // ─── File / Excel errors ────────────────────────────────────────────────

    public function testFileNotFound(): void
    {
        $this->artisan('petitions:import-appeals', ['file' => '/nonexistent/file.xlsx'])
            ->expectsOutput('File not found: /nonexistent/file.xlsx')
            ->assertFailed();
    }

    public function testJuristFileNotFound(): void
    {
        $this->stubFile();
        Excel::shouldReceive('toArray')->never();

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--file-jurist' => '/nonexistent/jurist.xlsx',
        ])
            ->expectsOutput('Jurist file not found: /nonexistent/jurist.xlsx')
            ->assertFailed();
    }

    public function testExcelThrowsException(): void
    {
        $this->stubFile();
        Excel::shouldReceive('toArray')->andThrow(new Exception('corrupt file'));

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('Error importing Excel file: corrupt file')
            ->assertFailed();
    }

    public function testEmptyExcel(): void
    {
        $this->stubFile();
        Excel::shouldReceive('toArray')->andReturn([[]]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('Excel file is empty')
            ->assertFailed();
    }

    public function testDepartmentNotFound(): void
    {
        $this->stubFile();
        Excel::shouldReceive('toArray')->andReturn([[['Juist Kenmerk', 'Binnenkomst']]]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('Department "wjz-bb" not found')
            ->assertFailed();
    }

    // ─── Row skipping ────────────────────────────────────────────────────────

    public function testSkipsRowsWithOnlyOneValue(): void
    {
        $this->wjzDepartment();
        $this->stubFile();

        // Row with exactly 1 non-null value → count($filtered) === 1 → skip
        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Naam', 'Binnenkomst'],
                [null, 'Jansen', null],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('Dry run completed. Would import 0 new petitions')
            ->assertSuccessful();
    }

    public function testSkipsRowsWithoutNumberKey(): void
    {
        $this->wjzDepartment();
        $this->stubFile();

        // Headers without 'Juist Kenmerk' → no 'number' key in $data
        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Naam', 'Directie'],
                ['Jansen', 'VGP'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('Dry run completed. Would import 0 new petitions')
            ->assertSuccessful();
    }

    public function testSkipsRowsWithFalsyNumber(): void
    {
        $this->wjzDepartment();
        $this->stubFile();

        // Number is null → array_combine yields null value → !isset($data['number']) → skip (line 157)
        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Naam', 'Binnenkomst'],
                [null, 'Jansen', '1-1-2023'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('Dry run completed. Would import 0 new petitions')
            ->assertSuccessful();
    }

    public function testSkipsRowsWithEmptyStringNumber(): void
    {
        $this->wjzDepartment();
        $this->stubFile();

        // Number is '' → isset is true but !$data['number'] is true → skip (line 160)
        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Naam', 'Binnenkomst'],
                ['', 'Jansen', '1-1-2023'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('Dry run completed. Would import 0 new petitions')
            ->assertSuccessful();
    }

    public function testUnknownColumnNameIsFallbackTransformed(): void
    {
        $this->wjzDepartment();
        $this->stubFile();

        // 'Opmerkingen' is not in $columnMapping → transformColumnName fallback return (line 620)
        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Opmerkingen'],
                [null, 'some note'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('Dry run completed. Would import 0 new petitions')
            ->assertSuccessful();
    }

    // ─── Jurist processing ──────────────────────────────────────────────────

    public function testJuristNotFoundAddsFailedRecord(): void
    {
        $this->wjzDepartment();
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Jurist'],
                ['2021000001', '1-1-2023', 'Onbekende Jurist'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('Jurist not found: Onbekende Jurist')
            ->assertSuccessful();
    }

    public function testJuristFoundByEmail(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $this->petitionStatus($type, 'Toebedeling');
        User::factory()->create(['name' => 'Other Name', 'email' => 'jan@example.com']);
        $this->stubFile();
        $this->stubJuristFile();

        Excel::shouldReceive('toArray')
            ->andReturn(
                [
                    [ // jurist file (first call)
                        ['name', 'email'],
                        ['Jan Jansen', 'jan@example.com'],
                    ],
                ],
                [
                    [ // main file (second call)
                        ['Juist Kenmerk', 'Binnenkomst', 'Soort', 'Jurist'],
                        ['2021000001', '1-1-2023', 'Beroep', 'Jan Jansen'],
                    ],
                ],
            );

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--file-jurist' => storage_path('app/' . $this->testJuristFilePath),
        ])
            ->expectsOutputToContain('Jurist "Jan Jansen" found by email: jan@example.com')
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->assertSuccessful();
    }

    public function testCommitCreatesPrimaryAssignment(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $this->petitionStatus($type, 'Toebedeling');
        $user = User::factory()->create(['name' => 'Jan Jansen']);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort', 'Jurist'],
                ['2021000001', '1-1-2023', 'Beroep', 'Jan Jansen'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])->assertSuccessful();

        $petition = Petition::query()->where('number', '2021000001')->first();
        $this->assertNotNull($petition);

        $this->assertDatabaseHas(PetitionAssignment::class, [
            'petition_id' => $petition->id,
            'user_id' => $user->id,
            'assignment_role' => AssignmentRole::PRIMARY->value,
        ]);
    }

    public function testDryRunLogsJuristAssignment(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $this->petitionStatus($type, 'Toebedeling');
        User::factory()->create(['name' => 'Jan Jansen']);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort', 'Jurist'],
                ['2021000001', '1-1-2023', 'Beroep', 'Jan Jansen'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('Would assign jurist as primary handler')
            ->assertSuccessful();
    }

    public function testSkipsExistingPrimaryAssignment(): void
    {
        $department = $this->wjzDepartment();
        $petition = Petition::factory()->create(['number' => '2021000001', 'department_id' => $department->id]);
        $user = User::factory()->create(['name' => 'Jan Jansen']);
        PetitionAssignment::factory()->create([
            'petition_id' => $petition->id,
            'user_id' => $user->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Jurist'],
                ['2021000001', 'Jan Jansen'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])->assertSuccessful();

        // Still only one assignment (not duplicated)
        $this->assertSame(
            1,
            PetitionAssignment::query()
                ->where('petition_id', $petition->id)
                ->where('assignment_role', AssignmentRole::PRIMARY)
                ->count(),
        );
    }

    // ─── Petition type processing ────────────────────────────────────────────

    public function testPetitionTypeNotFound(): void
    {
        $this->wjzDepartment();
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort'],
                ['2021000001', '1-1-2023', 'Onbestaand Type'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('Petition type not found: Onbestaand Type')
            ->assertSuccessful();
    }

    public function testStatusMappedFromUitspraakAndDryRunCreate(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        PetitionStatus::factory()->create([
            'status' => 'Uitspraak',
            'petition_type_id' => $type->id,
        ]);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort', 'Uitspraak'],
                ['2021000001', '1-1-2023', 'Beroep', 'ongegrond'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('Would create petition:')
            ->expectsOutput('Dry run completed. Would import 1 new petitions')
            ->assertSuccessful();
    }

    public function testStatusFromZitting(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        PetitionStatus::factory()->create([
            'status' => 'Zitting',
            'petition_type_id' => $type->id,
        ]);
        $this->stubFile();

        // No uitspraak, but zitting has a date → zitting status
        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort', 'Zitting'],
                ['2021000001', '1-1-2023', 'Beroep', '15-3-2023'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('Would create petition:')
            ->assertSuccessful();
    }

    public function testStatusDefaultToebedeling(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $this->petitionStatus($type, 'Toebedeling');
        $this->stubFile();

        // No uitspraak, no zitting → Toebedeling fallback
        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort'],
                ['2021000001', '1-1-2023', 'Beroep'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('Would create petition:')
            ->assertSuccessful();
    }

    public function testStatusNotDeterminedAddsFailedRecord(): void
    {
        $department = $this->wjzDepartment();
        $this->petitionType($department, 'Beroep');
        // No status of any kind created → all three status lookups fail → petition_status_id never set
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort'],
                ['2021000001', '1-1-2023', 'Beroep'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->expectsOutputToContain('petition_status_id could not be determined')
            ->assertSuccessful();

        $this->assertDatabaseMissing(Petition::class, ['number' => '2021000001']);
    }

    public function testNoPetitionTypeInDataUpdatesExistingPetition(): void
    {
        $department = $this->wjzDepartment();
        Petition::factory()->create([
            'number' => '2021000001',
            'department_id' => $department->id,
        ]);
        $this->stubFile();

        // Row with only number — no soort, no binnenkomst — petition_type_id never set
        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk'],
                ['2021000001'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('Dry run completed. Would import 0 new petitions')
            ->assertSuccessful();
    }

    // ─── Create / update petition ────────────────────────────────────────────

    public function testDateOfEntryRequired(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $this->petitionStatus($type, 'Toebedeling');
        $this->stubFile();

        // No binnenkomst column → date_of_entry missing
        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Soort'],
                ['2021000001', 'Beroep'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('date_of_entry is required')
            ->assertSuccessful();
    }

    public function testCommitCreatesNewPetitionAndStoresBatchId(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $this->petitionStatus($type, 'Toebedeling');
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort'],
                ['2021000001', '1-1-2023', 'Beroep'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->expectsOutput('Successfully imported 1 rows into petition table')
            ->expectsOutputToContain('Batch ID:')
            ->assertSuccessful();

        $this->assertDatabaseHas(Petition::class, [
            'number' => '2021000001',
            'department_id' => $department->id,
        ]);
    }

    public function testUpdatesExistingPetitionDryRun(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $this->petitionStatus($type, 'Toebedeling');
        Petition::factory()->create([
            'number' => '2021000001',
            'department_id' => $department->id,
        ]);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort'],
                ['2021000001', '1-1-2023', 'Beroep'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('Would update petition 2021000001:')
            ->assertSuccessful();
    }

    public function testUpdatesExistingPetitionCommit(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $this->petitionStatus($type, 'Toebedeling');
        Petition::factory()->create([
            'number' => '2021000001',
            'department_id' => $department->id,
        ]);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort'],
                ['2021000001', '5-6-2023', 'Beroep'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->expectsOutputToContain('Updated petition: 2021000001')
            ->expectsOutput('Successfully imported 0 rows into petition table')
            ->assertSuccessful();
    }

    public function testNoUpdateWhenPetitionDataEmpty(): void
    {
        $department = $this->wjzDepartment();
        Petition::factory()->create([
            'number' => '2021000001',
            'department_id' => $department->id,
        ]);
        $this->stubFile();

        // Row only has number → petitionUpdateData is empty → no update
        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk'],
                ['2021000001'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->expectsOutput('Successfully imported 0 rows into petition table')
            ->assertSuccessful();
    }

    // ─── Bezwaarde / contact ─────────────────────────────────────────────────

    public function testCommitCreatesContactAndLinks(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $this->petitionStatus($type, 'Toebedeling');
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort', 'Naam'],
                ['2021000001', '1-1-2023', 'Beroep', 'Jansen'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->assertSuccessful();

        $contact = Contact::query()->where('last_name', 'Jansen')->first();
        $this->assertNotNull($contact);

        $petition = Petition::query()->where('number', '2021000001')->first();
        $this->assertNotNull($petition);

        $this->assertDatabaseHas(ContactPetition::class, [
            'petition_id' => $petition->id,
            'contact_id' => $contact->id,
            'role' => 'applicant',
        ]);
    }

    public function testDryRunLinksContactMessage(): void
    {
        $department = $this->wjzDepartment();
        Petition::factory()->create([
            'number' => '2021000001',
            'department_id' => $department->id,
        ]);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Naam'],
                ['2021000001', 'Jansen'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('Would link contact "Jansen" to petition with role=applicant')
            ->assertSuccessful();
    }

    public function testLinksExistingContact(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $this->petitionStatus($type, 'Toebedeling');
        Contact::factory()->create(['last_name' => 'Jansen', 'department_id' => $department->id]);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort', 'Naam'],
                ['2021000001', '1-1-2023', 'Beroep', 'Jansen'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->assertSuccessful();

        // Only one contact with last_name Jansen (no duplicate created)
        $this->assertSame(1, Contact::query()->where('last_name', 'Jansen')->count());
    }

    public function testSkipsExistingContactLink(): void
    {
        $department = $this->wjzDepartment();
        $petition = Petition::factory()->create([
            'number' => '2021000001',
            'department_id' => $department->id,
        ]);
        $contact = Contact::factory()->create(['last_name' => 'Jansen', 'department_id' => $department->id]);
        ContactPetition::query()->create([
            'petition_id' => $petition->id,
            'contact_id' => $contact->id,
            'role' => 'applicant',
            'reference' => '',
        ]);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Naam'],
                ['2021000001', 'Jansen'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->assertSuccessful();

        // Still only one ContactPetition link
        $this->assertSame(
            1,
            ContactPetition::query()
                ->where('petition_id', $petition->id)
                ->where('contact_id', $contact->id)
                ->count(),
        );
    }

    // ─── Beleidsafdeling ─────────────────────────────────────────────────────

    public function testCommitLinksBeleidsafdeling(): void
    {
        $department = $this->wjzDepartment();
        $petition = Petition::factory()->create([
            'number' => '2021000001',
            'department_id' => $department->id,
        ]);
        PolicyDepartment::factory()->create(['name' => 'VGP']);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Directie'],
                ['2021000001', 'VGP'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->assertSuccessful();

        $policyDepartment = PolicyDepartment::query()->where('name', 'VGP')->first();
        $this->assertNotNull($policyDepartment);

        $this->assertTrue(
            DB::table('petition_policy_department')
                ->where('petition_id', $petition->id)
                ->where('policy_department_id', $policyDepartment->id)
                ->exists(),
        );
    }

    public function testBeleidsafdelingAlreadyLinked(): void
    {
        $department = $this->wjzDepartment();
        $petition = Petition::factory()->create([
            'number' => '2021000001',
            'department_id' => $department->id,
        ]);
        $policyDepartment = PolicyDepartment::factory()->create(['name' => 'VGP']);
        DB::table('petition_policy_department')->insert([
            'petition_id' => $petition->id,
            'policy_department_id' => $policyDepartment->id,
        ]);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Directie'],
                ['2021000001', 'VGP'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->assertSuccessful();

        // Still only one row (no duplicate)
        $this->assertSame(
            1,
            DB::table('petition_policy_department')
                ->where('petition_id', $petition->id)
                ->where('policy_department_id', $policyDepartment->id)
                ->count(),
        );
    }

    public function testDryRunLogsBeleidsafdeling(): void
    {
        $department = $this->wjzDepartment();
        Petition::factory()->create([
            'number' => '2021000001',
            'department_id' => $department->id,
        ]);
        PolicyDepartment::factory()->create(['name' => 'VGP']);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Directie'],
                ['2021000001', 'VGP'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('Would link policy department "VGP"')
            ->assertSuccessful();
    }

    public function testBeleidsafdelingNotFound(): void
    {
        $department = $this->wjzDepartment();
        Petition::factory()->create([
            'number' => '2021000001',
            'department_id' => $department->id,
        ]);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Directie'],
                ['2021000001', 'Onbekende Afdeling'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('Policy department not found: Onbekende Afdeling')
            ->assertSuccessful();
    }

    // ─── Reden intrekking ────────────────────────────────────────────────────

    public function testCommitRedenIntrekkingFromUitspraak(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $this->petitionStatus($type, 'Toebedeling');
        CustomPetitionProperty::factory()->create([
            'petition_type_id' => $type->id,
            'type' => CustomPetitionPropertyType::OPTION,
            'name' => 'Ongegrond',
        ]);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort', 'Uitspraak'],
                ['2021000001', '1-1-2023', 'Beroep', 'ongegrond'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->assertSuccessful();

        $petition = Petition::query()->where('number', '2021000001')->first();
        $property = CustomPetitionProperty::query()->where('name', 'Ongegrond')->first();
        $this->assertNotNull($petition);
        $this->assertNotNull($property);

        $this->assertTrue(
            DB::table('custom_petition_property_petition')
                ->where('petition_id', $petition->id)
                ->where('custom_petition_property_id', $property->id)
                ->exists(),
        );
    }

    public function testDryRunRedenIntrekking(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $this->petitionStatus($type, 'Uitspraak');
        CustomPetitionProperty::factory()->create([
            'petition_type_id' => $type->id,
            'type' => CustomPetitionPropertyType::OPTION,
            'name' => 'Gegrond',
        ]);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort', 'Uitspraak'],
                ['2021000001', '1-1-2023', 'Beroep', 'gegrond'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('Would add Reden intrekking: Gegrond')
            ->assertSuccessful();
    }

    public function testRedenIntrekkingPropertyNotFound(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $this->petitionStatus($type, 'Toebedeling');
        $this->stubFile();

        // 'ongegrond' maps to 'Ongegrond', but no such property in DB → silently skipped
        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort', 'Uitspraak'],
                ['2021000001', '1-1-2023', 'Beroep', 'ongegrond'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->assertSuccessful();
    }

    public function testRedenIntrekkingAlreadyExists(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $this->petitionStatus($type, 'Toebedeling');
        $property = CustomPetitionProperty::factory()->create([
            'petition_type_id' => $type->id,
            'type' => CustomPetitionPropertyType::OPTION,
            'name' => 'Ongegrond',
        ]);
        $petition = Petition::factory()->create([
            'number' => '2021000001',
            'department_id' => $department->id,
            'petition_type_id' => $type->id,
        ]);
        DB::table('custom_petition_property_petition')->insert([
            'petition_id' => $petition->id,
            'custom_petition_property_id' => $property->id,
        ]);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Uitspraak'],
                ['2021000001', 'ongegrond'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->assertSuccessful();

        // Still only one property link (not duplicated)
        $this->assertSame(
            1,
            DB::table('custom_petition_property_petition')
                ->where('petition_id', $petition->id)
                ->where('custom_petition_property_id', $property->id)
                ->count(),
        );
    }

    // ─── Custom dates ────────────────────────────────────────────────────────

    public function testCommitCreatesDatumUitspraak(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $this->petitionStatus($type, 'Toebedeling');
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort', 'Datum Uitspraak'],
                ['2021000001', '1-1-2023', 'Beroep', '15-3-2023'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->assertSuccessful();

        $petition = Petition::query()->where('number', '2021000001')->first();
        $this->assertNotNull($petition);

        $this->assertDatabaseHas(PetitionCustomDate::class, [
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_RULING->value,
            'date' => '2023-03-15',
        ]);
    }

    public function testDryRunLogsCustomDate(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $this->petitionStatus($type, 'Toebedeling');
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort', 'Zitting'],
                ['2021000001', '1-1-2023', 'Beroep', '20-4-2023'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('Would add custom date zitting:')
            ->assertSuccessful();
    }

    public function testSkipsExistingCustomDate(): void
    {
        $department = $this->wjzDepartment();
        $petition = Petition::factory()->create([
            'number' => '2021000001',
            'department_id' => $department->id,
        ]);
        PetitionCustomDate::factory()->create([
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_RULING,
            'date' => '2023-03-15',
        ]);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Datum Uitspraak'],
                ['2021000001', '15-3-2023'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->assertSuccessful();

        // Still only one custom date (not duplicated)
        $this->assertSame(
            1,
            PetitionCustomDate::query()
                ->where('petition_id', $petition->id)
                ->where('date_label', CustomDateLabel::DATE_RULING)
                ->count(),
        );
    }

    public function testInvalidDateFormatAddsFailedRecord(): void
    {
        $department = $this->wjzDepartment();
        Petition::factory()->create([
            'number' => '2021000001',
            'department_id' => $department->id,
        ]);
        $this->stubFile();

        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Datum Uitspraak'],
                ['2021000001', 'geen-datum'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('Invalid date format for datum_uitspraak: geen-datum')
            ->assertSuccessful();
    }

    public function testIntegerDateConvertedCorrectly(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $this->petitionStatus($type, 'Toebedeling');
        $this->stubFile();

        // Excel integer date 44927 = 2023-01-01
        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Soort', 'Datum Uitspraak'],
                ['2021000001', 44_927, 'Beroep', 44_927],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas(PetitionCustomDate::class, [
            'date_label' => CustomDateLabel::DATE_RULING->value,
            'date' => '2023-01-01',
        ]);
    }

    public function testEmptyDateOfEntryTriggersRequiredError(): void
    {
        $this->wjzDepartment();
        $this->stubFile();

        // date_of_entry = '' → convertDateFormat('') returns null (line 626) → "date_of_entry is required"
        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Naam'],
                ['2021000001', '', 'Jansen'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('date_of_entry is required')
            ->assertSuccessful();
    }

    public function testSlashDateFormatIsConvertedCorrectly(): void
    {
        $department = $this->wjzDepartment();
        $petition = Petition::factory()->create([
            'number' => '2021000001',
            'department_id' => $department->id,
        ]);
        $this->stubFile();

        // '1/5/23' is in n/j/y format (Jan 5 2023) → CalendarDate second try path (line 647)
        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Datum Uitspraak'],
                ['2021000001', '1/5/23'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas(PetitionCustomDate::class, [
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_RULING->value,
            'date' => '2023-01-05',
        ]);
    }

    // ─── Failed records display ──────────────────────────────────────────────

    public function testFailedRecordsAreDisplayed(): void
    {
        $this->wjzDepartment();
        $this->stubFile();

        // Petition not found → failedRecord with petition_number
        Excel::shouldReceive('toArray')->andReturn([
            [
                ['Juist Kenmerk', 'Binnenkomst', 'Jurist'],
                ['2099000001', '1-1-2023', 'Niemand'],
            ],
        ]);

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutputToContain('row(s) failed to import')
            ->expectsOutputToContain('Jurist not found: Niemand')
            ->expectsOutputToContain('Petition: 2099000001')
            ->assertSuccessful();
    }

    // ─── Rollback ────────────────────────────────────────────────────────────

    public function testRollbackBatchNotFound(): void
    {
        $this->artisan('petitions:import-appeals', ['--rollback' => 'import_nonexistent'])
            ->expectsOutputToContain('Batch ID not found: import_nonexistent')
            ->assertFailed();
    }

    public function testRollbackSuccessWithAllRecordTypes(): void
    {
        $department = $this->wjzDepartment();
        $type = $this->petitionType($department, 'Beroep');
        $status = $this->petitionStatus($type, 'Toebedeling');
        $user = User::factory()->create();

        $petition = Petition::query()->create([
            'number' => '2025TEST001',
            'department_id' => $department->id,
            'date_of_entry' => '2023-01-01',
            'petition_type_id' => $type->id,
            'petition_status_id' => $status->id,
        ]);
        $contact = Contact::factory()->create(['department_id' => $department->id]);
        $contactPetition = ContactPetition::query()->create([
            'petition_id' => $petition->id,
            'contact_id' => $contact->id,
            'role' => 'applicant',
            'reference' => '',
        ]);
        $customDate = PetitionCustomDate::factory()->create([
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_RULING,
        ]);
        $assignment = PetitionAssignment::factory()->create([
            'petition_id' => $petition->id,
            'user_id' => $user->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        $batchId = 'import_test_rollback';
        Cache::put($batchId, [
            'petitions' => [(string) $petition->id],
            'contacts' => [(string) $contact->id],
            'contact_petitions' => [(string) $contactPetition->id],
            'petition_custom_dates' => [(string) $customDate->id],
            'petition_assignments' => [(string) $assignment->id],
        ], now()->addDays(1));

        $this->artisan('petitions:import-appeals', ['--rollback' => $batchId])
            ->expectsOutputToContain('Rollback completed.')
            ->assertSuccessful();

        $this->assertDatabaseMissing(Petition::class, ['id' => $petition->id]);
        $this->assertDatabaseMissing(Contact::class, ['id' => $contact->id]);
        $this->assertDatabaseMissing(PetitionAssignment::class, ['id' => $assignment->id]);
        $this->assertNull(Cache::get($batchId));
    }

    public function testRollbackHandlesMissingPetition(): void
    {
        $batchId = 'import_test_missing';
        Cache::put($batchId, [
            'petitions' => ['00000000-0000-0000-0000-000000000000'],
        ], now()->addDays(1));

        $this->artisan('petitions:import-appeals', ['--rollback' => $batchId])
            ->expectsOutputToContain('Rollback completed.')
            ->assertSuccessful();
    }

    public function testRollbackDbError(): void
    {
        $department = $this->wjzDepartment();
        $petition = Petition::factory()->create(['department_id' => $department->id]);

        $batchId = 'import_test_dberror';
        Cache::put($batchId, [
            'petitions' => [(string) $petition->id],
        ], now()->addDays(1));

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION raise_rollback_appeals_test_exception()
            RETURNS TRIGGER AS $$
            BEGIN
                RAISE EXCEPTION 'Simulated rollback DB error';
            END;
            $$ LANGUAGE plpgsql
            SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER test_fail_appeals_rollback_delete
            BEFORE DELETE ON petitions
            FOR EACH STATEMENT EXECUTE FUNCTION raise_rollback_appeals_test_exception()
            SQL);

        $this->artisan('petitions:import-appeals', ['--rollback' => $batchId])
            ->expectsOutputToContain('Rollback failed:')
            ->assertFailed();
    }

    // ─── Jurist file loading ─────────────────────────────────────────────────

    public function testJuristFileEmpty(): void
    {
        $this->wjzDepartment();
        $this->stubFile();
        $this->stubJuristFile();

        Excel::shouldReceive('toArray')
            ->andReturn(
                [[]], // jurist file empty (first call)
                [[['Juist Kenmerk'], [null]]], // main file (second call, row skipped)
            );

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--file-jurist' => storage_path('app/' . $this->testJuristFilePath),
        ])
            ->expectsOutputToContain('Jurist Excel file is empty')
            ->assertSuccessful();
    }

    public function testJuristFileMissingColumns(): void
    {
        $this->wjzDepartment();
        $this->stubFile();
        $this->stubJuristFile();

        Excel::shouldReceive('toArray')
            ->andReturn(
                [[['foo', 'bar']]], // jurist file (first call, missing columns)
                [[['Juist Kenmerk'], [null]]], // main file (second call, row skipped)
            );

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--file-jurist' => storage_path('app/' . $this->testJuristFilePath),
        ])
            ->expectsOutputToContain('Jurist file must contain "name" and "email" columns')
            ->assertSuccessful();
    }

    public function testJuristFileLoadsSuccessfully(): void
    {
        $this->wjzDepartment();
        $this->stubFile();
        $this->stubJuristFile();

        Excel::shouldReceive('toArray')
            ->andReturn(
                [[['name', 'email'], ['Jan Jansen', 'jan@example.com']]], // jurist file (first call)
                [[['Juist Kenmerk'], [null]]], // main file (second call, row skipped)
            );

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--file-jurist' => storage_path('app/' . $this->testJuristFilePath),
        ])
            ->expectsOutputToContain('Loaded 1 jurist email mappings from file')
            ->assertSuccessful();
    }

    public function testJuristFileThrowsException(): void
    {
        $this->wjzDepartment();
        $this->stubFile();
        $this->stubJuristFile();

        Excel::shouldReceive('toArray')
            ->once()
            ->ordered()
            ->andThrow(new Exception('jurist file corrupt')); // jurist file (first call)
        Excel::shouldReceive('toArray')
            ->once()
            ->ordered()
            ->andReturn([[['Juist Kenmerk'], [null]]]); // main file (second call, row skipped)

        $this->artisan('petitions:import-appeals', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--file-jurist' => storage_path('app/' . $this->testJuristFilePath),
        ])
            ->expectsOutputToContain('Failed to load jurist email mapping: jurist file corrupt')
            ->assertSuccessful();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function wjzDepartment(): Department
    {
        return Department::factory()->create(['slug' => 'wjz-bb']);
    }

    private function petitionType(Department $department, string $name): PetitionType
    {
        return PetitionType::factory()->create([
            'name' => $name,
            'department_id' => $department->id,
        ]);
    }

    private function petitionStatus(PetitionType $type, string $status): PetitionStatus
    {
        return PetitionStatus::factory()->create([
            'status' => $status,
            'petition_type_id' => $type->id,
        ]);
    }

    private function stubFile(): void
    {
        file_put_contents(storage_path('app/' . $this->testFilePath), 'stub');
    }

    private function stubJuristFile(): void
    {
        file_put_contents(storage_path('app/' . $this->testJuristFilePath), 'stub');
    }
}
