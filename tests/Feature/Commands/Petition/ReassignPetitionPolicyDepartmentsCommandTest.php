<?php

declare(strict_types=1);

namespace Tests\Feature\Commands\Petition;

use App\Models\Petition;
use App\Models\PolicyDepartment;
use Illuminate\Support\Facades\DB;
use Tests\Feature\FeatureTestCase;

class ReassignPetitionPolicyDepartmentsCommandTest extends FeatureTestCase
{
    /** @var array<string, string> */
    private array $mappings = [
        'Sport' => 'Directie Sport',
        'COVID-19' => 'PD COVID-19',
        'DLV' => 'LZ',
    ];

    public function testDryRunWithNoDepartments(): void
    {
        $this->artisan('petitions:reassign-policy-departments')
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Run with --commit to apply the changes.')
            ->assertSuccessful();
    }

    public function testDryRunWithFromDepartmentButNoToDepartment(): void
    {
        PolicyDepartment::factory()->create(['name' => 'Sport']);

        $this->artisan('petitions:reassign-policy-departments')
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Run with --commit to apply the changes.')
            ->assertSuccessful();
    }

    public function testDryRunWithMatchingDepartmentsAndPetitions(): void
    {
        $this->createAllDepartmentMappings();

        $fromDept = PolicyDepartment::query()->withoutGlobalScopes()->where('name', 'Sport')->first();
        $petition = Petition::factory()->create();
        DB::table('petition_policy_department')->insert([
            'petition_id' => $petition->id,
            'policy_department_id' => $fromDept->id,
        ]);

        $this->artisan('petitions:reassign-policy-departments')
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Run with --commit to apply the changes.')
            ->assertSuccessful();

        $this->assertDatabaseHas('petition_policy_department', [
            'petition_id' => $petition->id,
            'policy_department_id' => $fromDept->id,
        ]);
    }

    public function testCommitFailsWhenFromDepartmentNotFound(): void
    {
        PolicyDepartment::factory()->create(['name' => 'Directie Sport']);

        $this->artisan('petitions:reassign-policy-departments', ['--commit' => true])
            ->expectsOutput('Some departments could not be resolved. Fix the issues above before committing.')
            ->assertFailed();
    }

    public function testCommitFailsWhenToDepartmentNotFound(): void
    {
        PolicyDepartment::factory()->create(['name' => 'Sport']);

        $this->artisan('petitions:reassign-policy-departments', ['--commit' => true])
            ->expectsOutput('Some departments could not be resolved. Fix the issues above before committing.')
            ->assertFailed();
    }

    public function testCommitSuccessfullyReassignsPetitions(): void
    {
        $this->createAllDepartmentMappings();

        $fromDept = PolicyDepartment::query()->withoutGlobalScopes()->where('name', 'Sport')->first();
        $toDept = PolicyDepartment::query()->withoutGlobalScopes()->where('name', 'Directie Sport')->first();

        $petition = Petition::factory()->create();
        DB::table('petition_policy_department')->insert([
            'petition_id' => $petition->id,
            'policy_department_id' => $fromDept->id,
        ]);

        $this->artisan('petitions:reassign-policy-departments', ['--commit' => true])
            ->expectsOutputToContain('Successfully reassigned')
            ->assertSuccessful();

        $this->assertDatabaseHas('petition_policy_department', [
            'petition_id' => $petition->id,
            'policy_department_id' => $toDept->id,
        ]);
        $this->assertDatabaseMissing('petition_policy_department', [
            'petition_id' => $petition->id,
            'policy_department_id' => $fromDept->id,
        ]);
    }

    public function testCommitDeduplicatesWhenPetitionAlreadyHasToDepartment(): void
    {
        $this->createAllDepartmentMappings();

        $fromDept = PolicyDepartment::query()->withoutGlobalScopes()->where('name', 'Sport')->first();
        $toDept = PolicyDepartment::query()->withoutGlobalScopes()->where('name', 'Directie Sport')->first();

        $petition = Petition::factory()->create();
        DB::table('petition_policy_department')->insert([
            ['petition_id' => $petition->id, 'policy_department_id' => $fromDept->id],
            ['petition_id' => $petition->id, 'policy_department_id' => $toDept->id],
        ]);

        $this->artisan('petitions:reassign-policy-departments', ['--commit' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('petition_policy_department', [
            'petition_id' => $petition->id,
            'policy_department_id' => $toDept->id,
        ]);
        $this->assertDatabaseMissing('petition_policy_department', [
            'petition_id' => $petition->id,
            'policy_department_id' => $fromDept->id,
        ]);
    }

    public function testCommitHandlesDbException(): void
    {
        $this->createAllDepartmentMappings();

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION raise_reassign_policy_depts_test_exception()
            RETURNS TRIGGER AS $$
            BEGIN
                RAISE EXCEPTION 'Simulated DB error for testing';
            END;
            $$ LANGUAGE plpgsql
            SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER test_fail_pivot_update
            BEFORE UPDATE ON petition_policy_department
            FOR EACH STATEMENT EXECUTE FUNCTION raise_reassign_policy_depts_test_exception()
            SQL);

        $this->artisan('petitions:reassign-policy-departments', ['--commit' => true])
            ->expectsOutputToContain('Error reassigning policy departments:')
            ->assertFailed();
    }

    private function createAllDepartmentMappings(): void
    {
        foreach ($this->mappings as $from => $to) {
            PolicyDepartment::factory()->create(['name' => $from]);
            PolicyDepartment::factory()->create(['name' => $to]);
        }
    }
}
