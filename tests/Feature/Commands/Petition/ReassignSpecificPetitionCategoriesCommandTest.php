<?php

declare(strict_types=1);

namespace Tests\Feature\Commands\Petition;

use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use Illuminate\Support\Facades\DB;
use Tests\Feature\FeatureTestCase;

use function array_keys;
use function array_unique;
use function array_values;

class ReassignSpecificPetitionCategoriesCommandTest extends FeatureTestCase
{
    /** @var array<string, string> */
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

    public function testDryRunWithNoPetitions(): void
    {
        $this->artisan('petitions:reassign-specific-categories')
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Run with --commit to apply the changes.')
            ->assertSuccessful();
    }

    public function testDryRunWithPetitionButNoCategory(): void
    {
        $department = Department::factory()->create();
        Petition::factory()->create(['number' => '2021000524', 'department_id' => $department->id]);

        $this->artisan('petitions:reassign-specific-categories')
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Run with --commit to apply the changes.')
            ->assertSuccessful();
    }

    public function testDryRunWithAllDataPresent(): void
    {
        $department = Department::factory()->create();
        $this->createAllPetitionsWithCategories($department);

        $this->artisan('petitions:reassign-specific-categories')
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Run with --commit to apply the changes.')
            ->assertSuccessful();
    }

    public function testCommitFailsWhenPetitionsAreMissing(): void
    {
        $this->artisan('petitions:reassign-specific-categories', ['--commit' => true])
            ->expectsOutput('Some petitions or categories could not be resolved. Fix the issues above before committing.')
            ->assertFailed();
    }

    public function testCommitFailsWhenCategoryIsMissing(): void
    {
        $department = Department::factory()->create();

        foreach (array_keys($this->mappings) as $number) {
            Petition::factory()->create(['number' => (string) $number, 'department_id' => $department->id]);
        }

        $this->artisan('petitions:reassign-specific-categories', ['--commit' => true])
            ->expectsOutput('Some petitions or categories could not be resolved. Fix the issues above before committing.')
            ->assertFailed();
    }

    public function testCommitSuccessfully(): void
    {
        $department = Department::factory()->create();
        $categories = $this->createAllPetitionsWithCategories($department);

        $this->artisan('petitions:reassign-specific-categories', ['--commit' => true])
            ->expectsOutputToContain('Successfully reassigned')
            ->assertSuccessful();

        $petition = Petition::query()->where('number', '2021000524')->first();
        $this->assertNotNull($petition);
        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'petition_category_id' => $categories['Geneesmiddelenwet (CIBG)']->id,
        ]);
    }

    public function testCommitHandlesDbException(): void
    {
        $department = Department::factory()->create();
        $this->createAllPetitionsWithCategories($department);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION raise_reassign_specific_categories_test_exception()
            RETURNS TRIGGER AS $$
            BEGIN
                RAISE EXCEPTION 'Simulated DB error for testing';
            END;
            $$ LANGUAGE plpgsql
            SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER test_fail_specific_petitions_update
            BEFORE UPDATE ON petitions
            FOR EACH STATEMENT EXECUTE FUNCTION raise_reassign_specific_categories_test_exception()
            SQL);

        $this->artisan('petitions:reassign-specific-categories', ['--commit' => true])
            ->expectsOutputToContain('Error reassigning categories:')
            ->assertFailed();
    }

    /**
     * @return array<string, PetitionCategory>
     */
    private function createAllPetitionsWithCategories(Department $department): array
    {
        $categoryNames = array_unique(array_values($this->mappings));
        $categories = [];

        foreach ($categoryNames as $name) {
            $categories[$name] = PetitionCategory::factory()->create([
                'name' => $name,
                'department_id' => $department->id,
            ]);
        }

        foreach (array_keys($this->mappings) as $number) {
            Petition::factory()->create([
                'number' => (string) $number,
                'department_id' => $department->id,
            ]);
        }

        return $categories;
    }
}
