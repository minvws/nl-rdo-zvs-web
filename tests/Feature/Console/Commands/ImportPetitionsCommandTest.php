<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Enums\CustomPetitionPropertyType;
use App\Enums\PetitionTypeType;
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
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Feature\FeatureTestCase;

use function file_exists;
use function now;
use function sys_get_temp_dir;
use function tempnam;
use function touch;
use function unlink;

final class ImportPetitionsCommandTest extends FeatureTestCase
{
    private string $tempFile;
    private string $tempJuristFile;
    private string $tempCategoryFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempFile = tempnam(sys_get_temp_dir(), 'import_test_');
        touch($this->tempFile);

        $this->tempJuristFile = tempnam(sys_get_temp_dir(), 'jurist_test_');
        touch($this->tempJuristFile);

        $this->tempCategoryFile = tempnam(sys_get_temp_dir(), 'category_test_');
        touch($this->tempCategoryFile);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }

        if (file_exists($this->tempJuristFile)) {
            unlink($this->tempJuristFile);
        }

        if (file_exists($this->tempCategoryFile)) {
            unlink($this->tempCategoryFile);
        }

        parent::tearDown();
    }

    // --- Rollback path (file argument is optional) ---

    #[Test]
    public function testRollbackWithoutFileArgumentFailsWhenBatchNotFound(): void
    {
        $this->artisan('petitions:import', ['--rollback' => 'nonexistent_batch_id'])
            ->expectsOutputToContain('Batch ID not found')
            ->assertFailed();
    }

    #[Test]
    public function testSuccessfulRollbackDeletesPetitions(): void
    {
        $department = $this->createWjzDepartment();
        $petition = Petition::factory()->create(['department_id' => $department->id]);

        $batchId = 'import_rollback_test';
        Cache::put($batchId, ['petitions' => [$petition->id]], now()->addDays(30));

        $this->artisan('petitions:import', ['--rollback' => $batchId])
            ->expectsOutputToContain('Rollback completed')
            ->assertSuccessful();

        $this->assertDatabaseMissing('petitions', ['id' => $petition->id]);
        $this->assertNull(Cache::get($batchId));
    }

    #[Test]
    public function testRollbackDeletesPetitionCustomDates(): void
    {
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $status = $this->createStatus($petitionType, 'Wachten op gronden bezwaar');
        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $status->id,
        ]);

        $customDate = PetitionCustomDate::query()->create([
            'petition_id' => $petition->id,
            'date_label' => 'date_decision_on_appeal',
            'date' => '2024-03-15',
        ]);

        $batchId = 'import_rollback_dates_test';
        Cache::put($batchId, [
            'petition_custom_dates' => [$customDate->id],
            'petitions' => [$petition->id],
        ], now()->addDays(30));

        $this->artisan('petitions:import', ['--rollback' => $batchId])
            ->expectsOutputToContain('Deleted 1 petition custom dates')
            ->assertSuccessful();

        $this->assertDatabaseMissing('petition_custom_dates', ['id' => $customDate->id]);
    }

    #[Test]
    public function testRollbackDeletesContactPetitions(): void
    {
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $status = $this->createStatus($petitionType, 'Wachten op gronden bezwaar');
        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $status->id,
        ]);
        $contact = Contact::factory()->create(['department_id' => $department->id]);

        $contactPetition = ContactPetition::query()->create([
            'petition_id' => $petition->id,
            'contact_id' => $contact->id,
            'role' => 'applicant',
        ]);

        $batchId = 'import_rollback_contact_petitions_test';
        Cache::put($batchId, [
            'contact_petitions' => [$contactPetition->id],
            'contacts' => [$contact->id],
            'petitions' => [$petition->id],
        ], now()->addDays(30));

        $this->artisan('petitions:import', ['--rollback' => $batchId])
            ->expectsOutputToContain('Deleted 1 contact petitions')
            ->expectsOutputToContain('Deleted 1 contacts')
            ->assertSuccessful();

        $this->assertDatabaseMissing('contact_petition', ['id' => $contactPetition->id]);
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    #[Test]
    public function testRollbackContinuesWhenPetitionNotFound(): void
    {
        $department = $this->createWjzDepartment();
        $petition = Petition::factory()->create(['department_id' => $department->id]);
        $fakePetitionId = '00000000-0000-0000-0000-000000000001';

        $batchId = 'import_rollback_missing_petition';
        // Include both a real and a non-existent petition ID
        Cache::put($batchId, ['petitions' => [$fakePetitionId, $petition->id]], now()->addDays(30));

        $this->artisan('petitions:import', ['--rollback' => $batchId])
            ->expectsOutputToContain('Rollback completed')
            ->assertSuccessful();
    }

    // --- File validation ---

    #[Test]
    public function testFileNotFoundReturnsFailure(): void
    {
        $this->artisan('petitions:import', ['file' => '/nonexistent/path/to/file.xlsx'])
            ->expectsOutputToContain('File not found')
            ->assertFailed();
    }

    #[Test]
    public function testJuristFileNotFoundReturnsFailure(): void
    {
        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--file-jurist' => '/nonexistent/jurist.xlsx',
        ])
            ->expectsOutputToContain('Jurist file not found')
            ->assertFailed();
    }

    #[Test]
    public function testCategoryFileNotFoundReturnsFailure(): void
    {
        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--file-category' => '/nonexistent/category.xlsx',
        ])
            ->expectsOutputToContain('Category file not found')
            ->assertFailed();
    }

    // --- Excel content validation ---

    #[Test]
    public function testEmptyExcelFileReturnsFailure(): void
    {
        Excel::shouldReceive('toArray')->once()->andReturn([[]]);

        $this->artisan('petitions:import', ['file' => $this->tempFile])
            ->expectsOutputToContain('Excel file is empty')
            ->assertFailed();
    }

    #[Test]
    public function testDepartmentNotFoundReturnsFailure(): void
    {
        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers'],
                ['BEZ-DEPT-001'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile])
            ->expectsOutputToContain('Department wjz-bb')
            ->assertFailed();
    }

    // --- Row skipping logic ---

    #[Test]
    public function testRowWithOnlyOneNonEmptyValueIsSkipped(): void
    {
        // Line 222-223: count(array_filter($row)) === 1 → row is skipped before number check
        $this->createWjzDepartment();

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status'],
                // Only one non-empty value (null is filtered out) → skipped at count===1 check
                ['BEZ-SKIP-001', null],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile])
            ->expectsOutputToContain('DRY RUN MODE')
            ->assertSuccessful();

        $this->assertDatabaseMissing('petitions', ['number' => 'BEZ-SKIP-001']);
    }

    #[Test]
    public function testRowWithoutMappedNumberColumnIsSkipped(): void
    {
        // Line 228: !isset($data['number']) → row is skipped
        // Headers don't contain 'zaaknummers', so no 'number' key after array_combine
        $this->createWjzDepartment();

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['ontvangstdatum bezwaar', 'status'],
                // 2 non-empty values, but no 'zaaknummers' header → no 'number' key
                ['15-3-2024', 'in behandeling'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile])
            ->expectsOutputToContain('DRY RUN MODE')
            ->assertSuccessful();
    }

    #[Test]
    public function testRowWithEmptyNumberIsSkipped(): void
    {
        // Line 231: !$data['number'] → row is skipped
        // '0' passes array_filter (not null/empty-string) but is falsy in PHP
        $this->createWjzDepartment();

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status'],
                // '0' is not filtered by array_filter (count=2) but is falsy → skipped at line 231
                ['0', 'in behandeling'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile])
            ->expectsOutputToContain('DRY RUN MODE')
            ->assertSuccessful();
    }

    // --- Dry run mode ---

    #[Test]
    public function testDryRunModeDoesNotCreatePetition(): void
    {
        // Row MUST have 2+ non-empty values to not be skipped
        $this->createWjzDepartment();

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status'],
                // 2 non-empty values → NOT skipped
                ['BEZ-DRY-001', 'in behandeling'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile])
            ->expectsOutputToContain('DRY RUN MODE')
            ->assertSuccessful();

        // Status lookup fails silently in dry-run (no status record created), but
        // since status is looked up and not found, it goes to failedRecords.
        // The petition is never created because status fails first.
        $this->assertDatabaseMissing('petitions', ['number' => 'BEZ-DRY-001']);
    }

    #[Test]
    public function testDryRunModeShowsWouldCreatePetition(): void
    {
        // Lines 424-432: dry run - would create petition
        // Need 2+ non-empty column values AND no status (so it doesn't fail at status lookup)
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'ontvangstdatum bezwaar'],
                ['BEZ-DRY-002', 'in behandeling', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile])
            ->expectsOutputToContain('DRY RUN MODE')
            ->expectsOutputToContain('Would create petition')
            ->assertSuccessful();

        $this->assertDatabaseMissing('petitions', ['number' => 'BEZ-DRY-002']);
    }

    #[Test]
    public function testDryRunModeShowsWouldUpdateExistingPetition(): void
    {
        // Lines 456-459: dry run - would update existing petition
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $status = $this->createStatus($petitionType, 'Wachten op gronden bezwaar');
        Petition::factory()->create([
            'department_id' => $department->id,
            'number' => 'BEZ-DRY-EXIST-001',
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $status->id,
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'ontvangstdatum bezwaar'],
                ['BEZ-DRY-EXIST-001', 'in behandeling', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile])
            ->expectsOutputToContain('DRY RUN MODE')
            ->expectsOutputToContain('Would update petition BEZ-DRY-EXIST-001')
            ->assertSuccessful();
    }

    #[Test]
    public function testDryRunModeShowsWouldLinkContact(): void
    {
        // Lines 496-498: dry run - would link contact
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $status = $this->createStatus($petitionType, 'Wachten op gronden bezwaar');
        Petition::factory()->create([
            'department_id' => $department->id,
            'number' => 'BEZ-DRY-CONTACT-001',
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $status->id,
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'bezwaarde', 'status'],
                ['BEZ-DRY-CONTACT-001', 'TestPerson, A.', 'in behandeling'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile])
            ->expectsOutputToContain('DRY RUN MODE')
            ->expectsOutputToContain('Would link contact')
            ->assertSuccessful();
    }

    // --- Commit mode: create petition ---

    #[Test]
    public function testCommitModeCreatesPetition(): void
    {
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'ontvangstdatum bezwaar'],
                ['BEZ-NEW-001', 'in behandeling', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', ['number' => 'BEZ-NEW-001']);
    }

    #[Test]
    public function testCommitStoresBatchIdInCache(): void
    {
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'ontvangstdatum bezwaar'],
                ['BEZ-BATCH-001', 'in behandeling', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->expectsOutputToContain('Batch ID:')
            ->assertSuccessful();
    }

    #[Test]
    public function testCommitModeWithDateOfEntry(): void
    {
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'ontvangstdatum bezwaar'],
                ['BEZ-DATE-001', 'in behandeling', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->assertSuccessful();

        $petition = Petition::query()->where('number', 'BEZ-DATE-001')->first();
        $this->assertNotNull($petition);
        $this->assertEquals('2024-03-15', $petition->date_of_entry->format('Y-m-d'));
    }

    // --- Commit mode: update existing petition ---

    #[Test]
    public function testCommitModeUpdatesPetition(): void
    {
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $status = $this->createStatus($petitionType, 'Wachten op gronden bezwaar');
        Petition::factory()->create([
            'department_id' => $department->id,
            'number' => 'BEZ-EXIST-001',
            'petition_type_id' => $petitionType->id,
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status'],
                ['BEZ-EXIST-001', 'in behandeling'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->assertSuccessful();

        $this->assertEquals(1, Petition::query()->where('number', 'BEZ-EXIST-001')->count());
        $this->assertDatabaseHas('petitions', [
            'number' => 'BEZ-EXIST-001',
            'petition_status_id' => $status->id,
        ]);
    }

    #[Test]
    public function testCommitModeUpdatesExistingContactPetitionReference(): void
    {
        // Lines 509-520: existing contact_petition updated with decision_reference
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $status = $this->createStatus($petitionType, 'Wachten op gronden bezwaar');
        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'number' => 'BEZ-CONTACT-REF-001',
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $status->id,
        ]);
        $contact = Contact::factory()->create([
            'department_id' => $department->id,
            'last_name' => 'Pietersen, B.',
        ]);
        // Pre-create a contact_petition so it already exists
        ContactPetition::query()->create([
            'petition_id' => $petition->id,
            'contact_id' => $contact->id,
            'role' => 'applicant',
            'reference' => 'OLD-REF',
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'bezwaarde', 'status', 'kenmerk primair besluit'],
                ['BEZ-CONTACT-REF-001', 'Pietersen, B.', 'in behandeling', 'NEW-REF-001'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->expectsOutputToContain('Updated reference for contact')
            ->assertSuccessful();

        $this->assertDatabaseHas('contact_petition', [
            'petition_id' => $petition->id,
            'contact_id' => $contact->id,
            'reference' => 'NEW-REF-001',
        ]);
    }

    // --- Commit mode: contact creation ---

    #[Test]
    public function testCommitModeCreatesContactForBezwaarde(): void
    {
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'bezwaarde', 'ontvangstdatum bezwaar'],
                ['BEZ-CONTACT-001', 'in behandeling', 'Jansen, A.', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('contacts', ['last_name' => 'Jansen, A.']);

        $petition = Petition::query()->where('number', 'BEZ-CONTACT-001')->first();
        $this->assertNotNull($petition);
        $this->assertDatabaseHas('contact_petition', [
            'petition_id' => $petition->id,
            'role' => 'applicant',
        ]);
    }

    // --- Policy department handling ---

    #[Test]
    public function testCommitModeLinksPolicyDepartment(): void
    {
        // Lines 533-566: beleidsafdeling handling - policy dept found
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');
        $policyDepartment = PolicyDepartment::factory()->create(['name' => 'TestAfdeling']);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'beleidsafdeling', 'ontvangstdatum bezwaar'],
                ['BEZ-DEPT-LINK-001', 'in behandeling', 'TestAfdeling', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->assertSuccessful();

        $petition = Petition::query()->where('number', 'BEZ-DEPT-LINK-001')->first();
        $this->assertNotNull($petition);
        $this->assertDatabaseHas('petition_policy_department', [
            'petition_id' => $petition->id,
            'policy_department_id' => $policyDepartment->id,
        ]);
    }

    #[Test]
    public function testPolicyDepartmentNotFoundAddsToFailedRecords(): void
    {
        // Lines 561-566: policy dept not found → failedRecords
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'beleidsafdeling', 'ontvangstdatum bezwaar'],
                ['BEZ-DEPT-FAIL-001', 'in behandeling', 'NonexistentAfdeling', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->expectsOutputToContain('Policy department not found')
            ->assertSuccessful();
    }

    #[Test]
    public function testDryRunModeShowsWouldLinkPolicyDepartment(): void
    {
        // Lines 554-559: dry run beleidsafdeling
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $status = $this->createStatus($petitionType, 'Wachten op gronden bezwaar');
        PolicyDepartment::factory()->create(['name' => 'DryRunAfdeling']);
        Petition::factory()->create([
            'department_id' => $department->id,
            'number' => 'BEZ-DRY-DEPT-001',
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $status->id,
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'beleidsafdeling'],
                ['BEZ-DRY-DEPT-001', 'in behandeling', 'DryRunAfdeling'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile])
            ->expectsOutputToContain('Would link policy department')
            ->assertSuccessful();
    }

    // --- Zwaarte property handling ---

    #[Test]
    public function testCommitModeHandlesZwaarteProperty(): void
    {
        // Lines 574-602: zwaarte property handling
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        $zwaarteProperty = CustomPetitionProperty::factory()->create([
            'petition_type_id' => $petitionType->id,
            'name' => 'Zwaar',
            'type' => CustomPetitionPropertyType::OPTION,
            'ordering' => 1,
            'grouping' => 1,
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'zwaarte', 'ontvangstdatum bezwaar'],
                ['BEZ-ZWAARTE-001', 'in behandeling', 'Zwaar', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->assertSuccessful();

        $petition = Petition::query()->where('number', 'BEZ-ZWAARTE-001')->first();
        $this->assertNotNull($petition);
        $this->assertDatabaseHas('custom_petition_property_petition', [
            'petition_id' => $petition->id,
            'custom_petition_property_id' => $zwaarteProperty->id,
        ]);
    }

    #[Test]
    public function testDryRunModeShowsWouldAddZwaarte(): void
    {
        // Lines 597-599: dry run zwaarte
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $status = $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        CustomPetitionProperty::factory()->create([
            'petition_type_id' => $petitionType->id,
            'name' => 'DryZwaar',
            'type' => CustomPetitionPropertyType::OPTION,
            'ordering' => 1,
            'grouping' => 1,
        ]);

        Petition::factory()->create([
            'department_id' => $department->id,
            'number' => 'BEZ-DRY-ZWAARTE-001',
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $status->id,
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'zwaarte', 'status'],
                ['BEZ-DRY-ZWAARTE-001', 'DryZwaar', 'in behandeling'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile])
            ->expectsOutputToContain('Would add Zwaarte: DryZwaar')
            ->assertSuccessful();
    }

    // --- Dictum BOB property handling ---

    #[Test]
    public function testCommitModeHandlesDictumBob(): void
    {
        // Lines 618-641: dictumbob property handling
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        $dictumProperty = CustomPetitionProperty::factory()->create([
            'petition_type_id' => $petitionType->id,
            'name' => 'Gegrond',
            'type' => CustomPetitionPropertyType::OPTION,
            'ordering' => 2,
            'grouping' => 2,
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'dictum bob', 'ontvangstdatum bezwaar'],
                ['BEZ-DICTUM-001', 'in behandeling', 'Gegrond', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->assertSuccessful();

        $petition = Petition::query()->where('number', 'BEZ-DICTUM-001')->first();
        $this->assertNotNull($petition);
        $this->assertDatabaseHas('custom_petition_property_petition', [
            'petition_id' => $petition->id,
            'custom_petition_property_id' => $dictumProperty->id,
        ]);
    }

    #[Test]
    public function testDryRunModeShowsWouldAddDictumBob(): void
    {
        // Lines 636-638: dry run dictumbob
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $status = $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        CustomPetitionProperty::factory()->create([
            'petition_type_id' => $petitionType->id,
            'name' => 'DryGegrond',
            'type' => CustomPetitionPropertyType::OPTION,
            'ordering' => 2,
            'grouping' => 2,
        ]);

        Petition::factory()->create([
            'department_id' => $department->id,
            'number' => 'BEZ-DRY-DICTUM-001',
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $status->id,
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'dictum bob', 'status'],
                ['BEZ-DRY-DICTUM-001', 'DryGegrond', 'in behandeling'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile])
            ->expectsOutputToContain('Would add Dictum BOB: DryGegrond')
            ->assertSuccessful();
    }

    // --- Reden intrekking property handling ---

    #[Test]
    public function testCommitModeHandlesRedenIntrekking(): void
    {
        // Lines 672-704: redenintrekking property handling (bezwaren mode)
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        $redenProperty = CustomPetitionProperty::factory()->create([
            'petition_type_id' => $petitionType->id,
            'name' => 'Overig',
            'type' => CustomPetitionPropertyType::OPTION,
            'ordering' => 3,
            'grouping' => 3,
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'reden intrekking', 'ontvangstdatum bezwaar'],
                ['BEZ-REDEN-001', 'in behandeling', 'overig', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->assertSuccessful();

        $petition = Petition::query()->where('number', 'BEZ-REDEN-001')->first();
        $this->assertNotNull($petition);
        $this->assertDatabaseHas('custom_petition_property_petition', [
            'petition_id' => $petition->id,
            'custom_petition_property_id' => $redenProperty->id,
        ]);
    }

    #[Test]
    public function testDryRunModeShowsWouldAddRedenIntrekking(): void
    {
        // Lines 696-700: dry run redenintrekking
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $status = $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        CustomPetitionProperty::factory()->create([
            'petition_type_id' => $petitionType->id,
            'name' => 'Informeel',
            'type' => CustomPetitionPropertyType::OPTION,
            'ordering' => 3,
            'grouping' => 3,
        ]);

        Petition::factory()->create([
            'department_id' => $department->id,
            'number' => 'BEZ-DRY-REDEN-001',
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $status->id,
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'reden intrekking', 'status'],
                ['BEZ-DRY-REDEN-001', 'informeel', 'in behandeling'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile])
            ->expectsOutputToContain('Would add Reden intrekking: Informeel')
            ->assertSuccessful();
    }

    // --- Custom date handling ---

    #[Test]
    public function testCommitModeCreatesCustomDateDatumBob(): void
    {
        // Lines 725-749: custom dates (datumbob)
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'datum bob', 'ontvangstdatum bezwaar'],
                ['BEZ-DATE-BOB-001', 'in behandeling', '10-5-2024', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->assertSuccessful();

        $petition = Petition::query()->where('number', 'BEZ-DATE-BOB-001')->first();
        $this->assertNotNull($petition);
        $this->assertDatabaseHas('petition_custom_dates', [
            'petition_id' => $petition->id,
            'date_label' => 'date_decision_on_appeal',
            'date' => '2024-05-10',
        ]);
    }

    #[Test]
    public function testInvalidDateFormatAddsToFailedRecords(): void
    {
        // Lines 748-753: invalid date → failedRecords
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'datum bob', 'ontvangstdatum bezwaar'],
                ['BEZ-DATE-INVALID-001', 'in behandeling', 'not-a-date', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->expectsOutputToContain('Invalid date format for datumbob')
            ->assertSuccessful();
    }

    #[Test]
    public function testDryRunModeShowsWouldAddCustomDate(): void
    {
        // Lines 734-739: dry run custom date
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $status = $this->createStatus($petitionType, 'Wachten op gronden bezwaar');
        Petition::factory()->create([
            'department_id' => $department->id,
            'number' => 'BEZ-DRY-DATE-001',
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $status->id,
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'datum bob'],
                ['BEZ-DRY-DATE-001', 'in behandeling', '10-5-2024'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile])
            ->expectsOutputToContain('Would add custom date datumbob')
            ->assertSuccessful();
    }

    // --- convertDateFormat: empty and integer ---

    #[Test]
    public function testConvertDateFormatReturnsNullForEmptyValue(): void
    {
        // Line 905: convertDateFormat('') returns null
        // Triggered via date_of_entry: isset($data['date_of_entry']) is true for '' but convertDateFormat returns null
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'ontvangstdatum bezwaar'],
                // ontvangstdatum bezwaar is '' → isset is true, convertDateFormat('') returns null → date_of_entry not set
                ['BEZ-DATE-EMPTY-001', 'in behandeling', ''],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->assertSuccessful();

        // date_of_entry is required (NOT NULL) — petition must not be created when it is empty
        $petition = Petition::query()->where('number', 'BEZ-DATE-EMPTY-001')->first();
        $this->assertNull($petition);
    }

    #[Test]
    public function testConvertDateFormatHandlesIntegerExcelDate(): void
    {
        // Lines 912-915: convertDateFormat integer date conversion
        // Excel serial date 45397 = 2024-03-15
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'datum bob', 'ontvangstdatum bezwaar'],
                // datum bob as integer (Excel serial date 45397)
                ['BEZ-DATE-INT-001', 'in behandeling', 45_397, '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->assertSuccessful();

        $petition = Petition::query()->where('number', 'BEZ-DATE-INT-001')->first();
        $this->assertNotNull($petition);
        // The custom date should have been created
        $this->assertEquals(
            1,
            PetitionCustomDate::query()->where('petition_id', $petition->id)->count(),
        );
    }

    // --- transformColumnName fallback ---

    #[Test]
    public function testTransformColumnNameFallsBackToSnakeCase(): void
    {
        // Line 896: transformColumnName fallback to snake_case
        // Use a column not in the mapping - it should be snake_cased
        // We need a real petition to exist so we can verify the column is processed without error.
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $status = $this->createStatus($petitionType, 'Wachten op gronden bezwaar');
        Petition::factory()->create([
            'department_id' => $department->id,
            'number' => 'BEZ-SNAKE-001',
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $status->id,
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                // 'Some Unknown Column' is not in mapping → converted to 'some_unknown_column'
                // 'zaaknummers' maps to 'number', 'status' maps to 'status'
                ['zaaknummers', 'Some Unknown Column', 'status'],
                ['BEZ-SNAKE-001', 'some_value', 'in behandeling'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile])
            ->expectsOutputToContain('DRY RUN MODE')
            ->assertSuccessful();

        // The row is processed without crash - the unknown column is snake_cased and ignored
        $this->assertDatabaseHas('petitions', ['number' => 'BEZ-SNAKE-001']);
    }

    // --- Failed records ---

    #[Test]
    public function testJuristNotFoundAddsToFailedRecords(): void
    {
        $this->createWjzDepartment();

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'jurist'],
                ['BEZ-FAIL-001', 'Nonexistent Person'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->expectsOutputToContain('Jurist not found')
            ->assertSuccessful();

        $this->assertDatabaseMissing('petitions', ['number' => 'BEZ-FAIL-001']);
    }

    #[Test]
    public function testJuristFoundByNameAssignsToPetition(): void
    {
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');
        $user = User::factory()->create(['name' => 'Jan de Boer']);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'jurist', 'ontvangstdatum bezwaar'],
                ['BEZ-JURIST-001', 'in behandeling', 'Jan de Boer', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->assertSuccessful();

        $petition = Petition::query()->where('number', 'BEZ-JURIST-001')->first();
        $this->assertNotNull($petition);
        $this->assertEquals($user->id, $petition->assigned_to);
    }

    #[Test]
    public function testJuristFoundViaEmailMapping(): void
    {
        // Lines 243-250: jurist found via email mapping
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');
        $user = User::factory()->create([
            'name' => 'Email Jurist',
            'email' => 'emailjurist@example.com',
        ]);

        // First Excel call: jurist file (loadJuristEmailMapping)
        // Second Excel call: main file
        Excel::shouldReceive('toArray')
            ->andReturn(
                // First call: jurist file data
                [
                    [
                        ['name', 'email'],
                        ['Email Jurist Via File', 'emailjurist@example.com'],
                    ],
                ],
                // Second call: main file data
                [
                    [
                        ['zaaknummers', 'status', 'jurist', 'ontvangstdatum bezwaar'],
                        ['BEZ-EMAIL-JURIST-001', 'in behandeling', 'Email Jurist Via File', '15-3-2024'],
                    ],
                ],
            );

        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--file-jurist' => $this->tempJuristFile,
            '--commit' => true,
        ])
            ->expectsOutputToContain('found by email')
            ->assertSuccessful();

        $petition = Petition::query()->where('number', 'BEZ-EMAIL-JURIST-001')->first();
        $this->assertNotNull($petition);
        $this->assertEquals($user->id, $petition->assigned_to);
    }

    #[Test]
    public function testStatusNotFoundAddsToFailedRecords(): void
    {
        $this->createWjzDepartment();

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status'],
                ['BEZ-FAIL-002', 'Onbekende status xyz'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->expectsOutputToContain('Status not found')
            ->assertSuccessful();

        $this->assertDatabaseMissing('petitions', ['number' => 'BEZ-FAIL-002']);
    }

    #[Test]
    public function testCategoryNotFoundAddsToFailedRecords(): void
    {
        $this->createWjzDepartment();

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'categorie'],
                ['BEZ-FAIL-003', 'Onbekende categorie xyz'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->expectsOutputToContain('Category not found')
            ->assertSuccessful();

        $this->assertDatabaseMissing('petitions', ['number' => 'BEZ-FAIL-003']);
    }

    #[Test]
    public function testCategoryFoundAndLinkedToPetition(): void
    {
        // Lines 398-399: category found and linked (petition_category_id set)
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');
        $category = PetitionCategory::query()->create([
            'name' => 'TestCategorie',
            'department_id' => $department->id,
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'categorie', 'ontvangstdatum bezwaar'],
                ['BEZ-CATEGORY-001', 'in behandeling', 'TestCategorie', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->assertSuccessful();

        $petition = Petition::query()->where('number', 'BEZ-CATEGORY-001')->first();
        $this->assertNotNull($petition);
        $this->assertEquals($category->id, $petition->petition_category_id);
    }

    // --- Category mapping via file ---

    #[Test]
    public function testCategoryMappedViaCategoryFile(): void
    {
        // Lines 379-383: category mapped via categoryMapping file
        // Lines 398-399: category found and linked
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');
        $category = PetitionCategory::query()->create([
            'name' => 'TargetCategorie',
            'department_id' => $department->id,
        ]);

        // First Excel call: category file (loadCategoryMapping)
        // Second Excel call: main file
        Excel::shouldReceive('toArray')
            ->andReturn(
                // First call: category file data
                [
                    [
                        ['categorie_in_sheet', 'categorie'],
                        ['SheetCategorie', 'TargetCategorie'],
                    ],
                ],
                // Second call: main file data
                [
                    [
                        ['zaaknummers', 'status', 'categorie', 'ontvangstdatum bezwaar'],
                        ['BEZ-CAT-MAP-001', 'in behandeling', 'SheetCategorie', '15-3-2024'],
                    ],
                ],
            );

        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--file-category' => $this->tempCategoryFile,
            '--commit' => true,
        ])
            ->expectsOutputToContain('Category "SheetCategorie" mapped to: TargetCategorie')
            ->assertSuccessful();

        $petition = Petition::query()->where('number', 'BEZ-CAT-MAP-001')->first();
        $this->assertNotNull($petition);
        $this->assertEquals($category->id, $petition->petition_category_id);
    }

    // --- Beroepen mapping ---

    #[Test]
    public function testBeroepenFlagUsesAlternativeColumnMapping(): void
    {
        $department = $this->createWjzDepartment();
        $petitionType = PetitionType::factory()->create([
            'department_id' => $department->id,
            'name' => 'Beroep',
            'type' => PetitionTypeType::BEROEP,
        ]);
        PetitionStatus::factory()->create([
            'petition_type_id' => $petitionType->id,
            'status' => 'Toebedeling',
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['juist kenmerk', 'soort', 'binnenkomst'],
                ['BER-BEROEP-001', 'Beroep', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--commit' => true,
            '--beroepen' => true,
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', ['number' => 'BER-BEROEP-001']);
    }

    #[Test]
    public function testBeroepenPetitionTypeNotFoundAddsToFailedRecords(): void
    {
        // Lines 301-306: beroepen mode - petition type not found → failedRecords
        $this->createWjzDepartment();

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['juist kenmerk', 'soort', 'binnenkomst'],
                ['BER-NO-TYPE-001', 'NonexistentType', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--commit' => true,
            '--beroepen' => true,
        ])
            ->expectsOutputToContain('Petition type not found')
            ->assertSuccessful();

        $this->assertDatabaseMissing('petitions', ['number' => 'BER-NO-TYPE-001']);
    }

    #[Test]
    public function testBeroepenUitspraakMapsToStatus(): void
    {
        // Lines 312-335: beroepen uitspraak → maps to petition_status_id
        $department = $this->createWjzDepartment();
        $petitionType = PetitionType::factory()->create([
            'department_id' => $department->id,
            'name' => 'Beroep',
            'type' => PetitionTypeType::BEROEP,
        ]);
        $uitspraakStatus = PetitionStatus::factory()->create([
            'petition_type_id' => $petitionType->id,
            'status' => 'Uitspraak',
        ]);
        PetitionStatus::factory()->create([
            'petition_type_id' => $petitionType->id,
            'status' => 'Toebedeling',
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['juist kenmerk', 'soort', 'uitspraak', 'binnenkomst'],
                ['BER-UITSPRAAK-001', 'Beroep', 'gegrond', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--commit' => true,
            '--beroepen' => true,
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'number' => 'BER-UITSPRAAK-001',
            'petition_status_id' => $uitspraakStatus->id,
        ]);
    }

    #[Test]
    public function testBeroepenZittingStatusFallback(): void
    {
        // Lines 342-348: beroepen zitting → status fallback
        $department = $this->createWjzDepartment();
        $petitionType = PetitionType::factory()->create([
            'department_id' => $department->id,
            'name' => 'Beroep',
            'type' => PetitionTypeType::BEROEP,
        ]);
        $zittingStatus = PetitionStatus::factory()->create([
            'petition_type_id' => $petitionType->id,
            'status' => 'Zitting',
        ]);
        PetitionStatus::factory()->create([
            'petition_type_id' => $petitionType->id,
            'status' => 'Toebedeling',
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                // No uitspraak, but zitting has a date → zitting status fallback
                ['juist kenmerk', 'soort', 'zitting', 'binnenkomst'],
                ['BER-ZITTING-001', 'Beroep', '20-4-2024', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--commit' => true,
            '--beroepen' => true,
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'number' => 'BER-ZITTING-001',
            'petition_status_id' => $zittingStatus->id,
        ]);
    }

    #[Test]
    public function testBeroepenUitspraakMapsToRedenIntrekking(): void
    {
        // Lines 667-668: beroepen uitspraak → redenintrekking mapping
        $department = $this->createWjzDepartment();
        $petitionType = PetitionType::factory()->create([
            'department_id' => $department->id,
            'name' => 'Beroep',
            'type' => PetitionTypeType::BEROEP,
        ]);
        PetitionStatus::factory()->create([
            'petition_type_id' => $petitionType->id,
            'status' => 'Uitspraak',
        ]);
        PetitionStatus::factory()->create([
            'petition_type_id' => $petitionType->id,
            'status' => 'Toebedeling',
        ]);

        $redenProperty = CustomPetitionProperty::factory()->create([
            'petition_type_id' => $petitionType->id,
            'name' => 'Gegrond',
            'type' => CustomPetitionPropertyType::OPTION,
            'ordering' => 1,
            'grouping' => 1,
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['juist kenmerk', 'soort', 'uitspraak', 'binnenkomst'],
                ['BER-REDEN-UITSPRAAK-001', 'Beroep', 'gegrond', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--commit' => true,
            '--beroepen' => true,
        ])
            ->assertSuccessful();

        $petition = Petition::query()->where('number', 'BER-REDEN-UITSPRAAK-001')->first();
        $this->assertNotNull($petition);
        $this->assertDatabaseHas('custom_petition_property_petition', [
            'petition_id' => $petition->id,
            'custom_petition_property_id' => $redenProperty->id,
        ]);
    }

    #[Test]
    public function testBeroepenPetitionTypeIdUpdatedForExistingPetition(): void
    {
        // Lines 608-609: update petition_type_id in non-dry-run (beroepen mode, existing petition)
        $department = $this->createWjzDepartment();
        $oldPetitionType = $this->createBezwaarPetitionType($department);
        $oldStatus = $this->createStatus($oldPetitionType, 'Wachten op gronden bezwaar');

        $newPetitionType = PetitionType::factory()->create([
            'department_id' => $department->id,
            'name' => 'Beroep',
            'type' => PetitionTypeType::BEROEP,
        ]);
        PetitionStatus::factory()->create([
            'petition_type_id' => $newPetitionType->id,
            'status' => 'Toebedeling',
        ]);

        Petition::factory()->create([
            'department_id' => $department->id,
            'number' => 'BER-TYPE-UPDATE-001',
            'petition_type_id' => $oldPetitionType->id,
            'petition_status_id' => $oldStatus->id,
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['juist kenmerk', 'soort', 'binnenkomst'],
                ['BER-TYPE-UPDATE-001', 'Beroep', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--commit' => true,
            '--beroepen' => true,
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', [
            'number' => 'BER-TYPE-UPDATE-001',
            'petition_type_id' => $newPetitionType->id,
        ]);
    }

    #[Test]
    public function testDryRunModeShowsWouldUpdatePetitionTypeId(): void
    {
        // Line 611: dry run - would update petition_type_id
        $department = $this->createWjzDepartment();
        $oldPetitionType = $this->createBezwaarPetitionType($department);
        $oldStatus = $this->createStatus($oldPetitionType, 'Wachten op gronden bezwaar');

        $newPetitionType = PetitionType::factory()->create([
            'department_id' => $department->id,
            'name' => 'Beroep',
            'type' => PetitionTypeType::BEROEP,
        ]);
        PetitionStatus::factory()->create([
            'petition_type_id' => $newPetitionType->id,
            'status' => 'Toebedeling',
        ]);

        Petition::factory()->create([
            'department_id' => $department->id,
            'number' => 'BER-DRY-TYPE-001',
            'petition_type_id' => $oldPetitionType->id,
            'petition_status_id' => $oldStatus->id,
        ]);

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['juist kenmerk', 'soort', 'binnenkomst'],
                ['BER-DRY-TYPE-001', 'Beroep', '15-3-2024'],
            ],
        ]);

        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--beroepen' => true,
        ])
            ->expectsOutputToContain('Would update petition_type_id for petition BER-DRY-TYPE-001')
            ->assertSuccessful();
    }

    // --- loadJuristEmailMapping full method body ---

    #[Test]
    public function testLoadJuristEmailMappingLoadsCorrectly(): void
    {
        // Lines 924-980: loadJuristEmailMapping full method body - data rows
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        $user = User::factory()->create([
            'email' => 'loaded.jurist@example.com',
            'name' => 'Loaded Jurist',
        ]);

        Excel::shouldReceive('toArray')
            ->andReturn(
                // First call: jurist file - valid data
                [
                    [
                        ['name', 'email'],
                        ['Loaded Via File', 'loaded.jurist@example.com'],
                    ],
                ],
                // Second call: main file
                [
                    [
                        ['zaaknummers', 'status', 'jurist', 'ontvangstdatum bezwaar'],
                        ['BEZ-LOAD-JURIST-001', 'in behandeling', 'Loaded Via File', '15-3-2024'],
                    ],
                ],
            );

        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--file-jurist' => $this->tempJuristFile,
            '--commit' => true,
        ])
            ->expectsOutputToContain('Loaded 1 jurist email mappings from file')
            ->assertSuccessful();

        $petition = Petition::query()->where('number', 'BEZ-LOAD-JURIST-001')->first();
        $this->assertNotNull($petition);
        $this->assertEquals($user->id, $petition->assigned_to);
    }

    #[Test]
    public function testLoadJuristEmailMappingWithEmptyFile(): void
    {
        // Lines 945-948: empty jurist file → warns and returns
        $this->createWjzDepartment();

        Excel::shouldReceive('toArray')
            ->andReturn(
                // First call: jurist file - empty
                [[]],
                // Second call: main file - just headers to pass validation
                [
                    [
                        ['zaaknummers', 'status'],
                    ],
                ],
            );

        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--file-jurist' => $this->tempJuristFile,
        ])
            ->expectsOutputToContain('Jurist Excel file is empty')
            ->assertSuccessful();
    }

    #[Test]
    public function testLoadJuristEmailMappingWithMissingColumns(): void
    {
        // Lines 959-961: jurist file missing columns → warns and returns
        $this->createWjzDepartment();

        Excel::shouldReceive('toArray')
            ->andReturn(
                // First call: jurist file - wrong columns
                [
                    [
                        ['wrong_col_a', 'wrong_col_b'],
                        ['Some Name', 'some@email.com'],
                    ],
                ],
                // Second call: main file - just headers to pass validation
                [
                    [
                        ['zaaknummers', 'status'],
                    ],
                ],
            );

        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--file-jurist' => $this->tempJuristFile,
        ])
            ->expectsOutputToContain('Jurist file must contain "name" and "email" columns')
            ->assertSuccessful();
    }

    #[Test]
    public function testLoadJuristEmailMappingSkipsRowsWithMissingNameOrEmail(): void
    {
        // Row with null name/email is not added to mapping
        $this->createWjzDepartment();

        Excel::shouldReceive('toArray')
            ->andReturn(
                // First call: jurist file - one row with null email
                [
                    [
                        ['name', 'email'],
                        ['Some Name', null], // null email → skipped
                        [null, 'some@email.com'], // null name → skipped
                    ],
                ],
                // Second call: main file - just headers to pass validation
                [
                    [
                        ['zaaknummers', 'status'],
                    ],
                ],
            );

        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--file-jurist' => $this->tempJuristFile,
        ])
            ->expectsOutputToContain('Loaded 0 jurist email mappings from file')
            ->assertSuccessful();
    }

    // --- loadCategoryMapping full method body ---

    #[Test]
    public function testLoadCategoryMappingLoadsCorrectly(): void
    {
        // Lines 988-1028: loadCategoryMapping full method body
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');
        $category = PetitionCategory::query()->create([
            'name' => 'MappedCategory',
            'department_id' => $department->id,
        ]);

        Excel::shouldReceive('toArray')
            ->andReturn(
                // First call: category file
                [
                    [
                        ['categorie_in_sheet', 'categorie'],
                        ['SheetName', 'MappedCategory'],
                    ],
                ],
                // Second call: main file
                [
                    [
                        ['zaaknummers', 'status', 'categorie', 'ontvangstdatum bezwaar'],
                        ['BEZ-CATMAP-001', 'in behandeling', 'SheetName', '15-3-2024'],
                    ],
                ],
            );

        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--file-category' => $this->tempCategoryFile,
            '--commit' => true,
        ])
            ->expectsOutputToContain('Loaded 1 category mappings from file')
            ->assertSuccessful();

        $petition = Petition::query()->where('number', 'BEZ-CATMAP-001')->first();
        $this->assertNotNull($petition);
        $this->assertEquals($category->id, $petition->petition_category_id);
    }

    #[Test]
    public function testLoadCategoryMappingWithEmptyFile(): void
    {
        // category file is empty → warns and returns
        $this->createWjzDepartment();

        Excel::shouldReceive('toArray')
            ->andReturn(
                // First call: category file - empty
                [[]],
                // Second call: main file - just headers
                [
                    [
                        ['zaaknummers', 'status'],
                    ],
                ],
            );

        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--file-category' => $this->tempCategoryFile,
        ])
            ->expectsOutputToContain('Category Excel file is empty')
            ->assertSuccessful();
    }

    #[Test]
    public function testLoadCategoryMappingWithMissingColumns(): void
    {
        // category file missing required columns → warns and returns
        $this->createWjzDepartment();

        Excel::shouldReceive('toArray')
            ->andReturn(
                // First call: category file - wrong columns
                [
                    [
                        ['wrong_col_a', 'wrong_col_b'],
                        ['SheetName', 'DBName'],
                    ],
                ],
                // Second call: main file - just headers
                [
                    [
                        ['zaaknummers', 'status'],
                    ],
                ],
            );

        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--file-category' => $this->tempCategoryFile,
        ])
            ->expectsOutputToContain('Category file must contain "CATEGORIE_IN_SHEET" and "CATEGORIE" columns')
            ->assertSuccessful();
    }

    #[Test]
    public function testLoadCategoryMappingSkipsRowsWithMissingValues(): void
    {
        // Rows with null values are skipped
        $this->createWjzDepartment();

        Excel::shouldReceive('toArray')
            ->andReturn(
                // First call: category file - rows with nulls
                [
                    [
                        ['categorie_in_sheet', 'categorie'],
                        [null, 'MappedCategory'], // null sheet name → skipped
                        ['SheetName', null], // null target → skipped
                    ],
                ],
                // Second call: main file - just headers
                [
                    [
                        ['zaaknummers', 'status'],
                    ],
                ],
            );

        $this->artisan('petitions:import', [
            'file' => $this->tempFile,
            '--file-category' => $this->tempCategoryFile,
        ])
            ->expectsOutputToContain('Loaded 0 category mappings from file')
            ->assertSuccessful();
    }

    // --- Rollback edge cases ---

    #[Test]
    public function testRollbackHandlesDbException(): void
    {
        // Lines 873-876: rollback DB exception
        // We trigger a real DB error by providing an invalid UUID that cannot be cast,
        // which causes an exception during the deletion phase inside the transaction.
        // Instead, we use an invalid petition_custom_dates ID to cause a constraint/cast error.
        //
        // Strategy: store a non-UUID string in petition_custom_dates IDs list so that
        // PetitionCustomDate::whereIn throws a DB cast exception.
        $batchId = 'import_rollback_db_error_test';
        // Use an invalid UUID format to trigger a PostgreSQL cast error
        Cache::put($batchId, ['petition_custom_dates' => ['not-a-valid-uuid-!@#']], now()->addDays(30));

        $this->artisan('petitions:import', ['--rollback' => $batchId])
            ->expectsOutputToContain('Rollback failed')
            ->assertFailed();
    }

    // --- Exception handling ---

    #[Test]
    public function testExceptionDuringImportReturnsFailure(): void
    {
        // Lines 796-799: catch (Throwable) during import
        $this->createWjzDepartment();

        Excel::shouldReceive('toArray')->once()->andThrow(new RuntimeException('Disk read error'));

        $this->artisan('petitions:import', ['file' => $this->tempFile])
            ->expectsOutputToContain('Error importing Excel file')
            ->assertFailed();
    }

    #[Test]
    public function testConvertDateFormatHandlesNjyFormat(): void
    {
        // Line 929: n/j/y date format fallback (e.g. '1/18/23')
        $department = $this->createWjzDepartment();
        $petitionType = $this->createBezwaarPetitionType($department);
        $this->createStatus($petitionType, 'Wachten op gronden bezwaar');

        Excel::shouldReceive('toArray')->once()->andReturn([
            [
                ['zaaknummers', 'status', 'ontvangstdatum bezwaar'],
                // n/j/y format — fails j-n-Y parse, succeeds with n/j/y
                ['BEZ-NJY-001', 'in behandeling', '3/15/24'],
            ],
        ]);

        $this->artisan('petitions:import', ['file' => $this->tempFile, '--commit' => true])
            ->assertSuccessful();

        $petition = Petition::query()->where('number', 'BEZ-NJY-001')->first();
        $this->assertNotNull($petition);
        $this->assertEquals('2024-03-15', $petition->date_of_entry->format('Y-m-d'));
    }

    #[Test]
    public function testLoadJuristEmailMappingHandlesException(): void
    {
        // Lines 978-979: catch (Throwable) in loadJuristEmailMapping
        $this->createWjzDepartment();
        $juristFile = tempnam(sys_get_temp_dir(), 'jurist_err_');
        touch($juristFile);

        try {
            // First call (jurist file) throws; second call (main import) returns header-only sheet
            Excel::shouldReceive('toArray')
                ->once()
                ->andThrow(new RuntimeException('File read error'));

            Excel::shouldReceive('toArray')
                ->once()
                ->andReturn([[['zaaknummers', 'status']]]);

            $this->artisan('petitions:import', [
                'file' => $this->tempFile,
                '--file-jurist' => $juristFile,
            ])
                ->expectsOutputToContain('Failed to load jurist email mapping')
                ->assertSuccessful();
        } finally {
            if (file_exists($juristFile)) {
                unlink($juristFile);
            }
        }
    }

    #[Test]
    public function testLoadCategoryMappingHandlesException(): void
    {
        // Lines 1027-1028: catch (Throwable) in loadCategoryMapping
        $this->createWjzDepartment();
        $categoryFile = tempnam(sys_get_temp_dir(), 'cat_err_');
        touch($categoryFile);

        try {
            // First call (category file) throws; second call (main import) returns header-only sheet
            Excel::shouldReceive('toArray')
                ->once()
                ->andThrow(new RuntimeException('File read error'));

            Excel::shouldReceive('toArray')
                ->once()
                ->andReturn([[['zaaknummers', 'status']]]);

            $this->artisan('petitions:import', [
                'file' => $this->tempFile,
                '--file-category' => $categoryFile,
            ])
                ->expectsOutputToContain('Failed to load category mapping')
                ->assertSuccessful();
        } finally {
            if (file_exists($categoryFile)) {
                unlink($categoryFile);
            }
        }
    }

    // --- Helpers ---

    private function createWjzDepartment(): Department
    {
        return Department::factory()->create(['slug' => 'wjz-bb']);
    }

    private function createBezwaarPetitionType(Department $department): PetitionType
    {
        return PetitionType::factory()->create([
            'department_id' => $department->id,
            'type' => PetitionTypeType::BEZWAAR,
        ]);
    }

    private function createStatus(PetitionType $petitionType, string $statusName): PetitionStatus
    {
        return PetitionStatus::factory()->create([
            'petition_type_id' => $petitionType->id,
            'status' => $statusName,
        ]);
    }
}
