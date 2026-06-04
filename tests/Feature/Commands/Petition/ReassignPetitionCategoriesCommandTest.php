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

class ReassignPetitionCategoriesCommandTest extends FeatureTestCase
{
    /** @var array<string, string> */
    private array $mappings = [
        'subsidieregeling Tegemoetkoming amateursportorgani' => 'Subsidieregeling tegemoetkoming amateursportorgani',
        'Subsidieregeling Stageplaatsenzorg II' => 'Subsidieregeling stageplaatsen zorg',
        'Subsidieregeling tegemoetkoming verhuurders sporta' => 'Subsidieregeling tegemoetkoming verhuurders amateursportorgani',
        'Alcoholwet Lcsh' => 'Alcoholwet LCSH',
        'AWB' => 'Awb',
        'Instellingssubsidie Patiënten en gehandicaptenorga' => 'Subsidieregeling PG organisaties 2024-2028',
        'Subsidieregeling pg organisaties 2024-2028' => 'Subsidieregeling PG organisaties 2024-2028',
        'subsidieregeling VIPP inzicht PGO' => 'Subsidieregeling VIPP inzicht PGO',
        'Subsidieverlening PrEP' => 'Subsidieregeling PrEP',
    ];

    public function testDryRunWithNoCategories(): void
    {
        $this->artisan('petitions:reassign-categories')
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Run with --commit to apply the changes.')
            ->assertSuccessful();
    }

    public function testDryRunWithFromCategoryButNoToCategory(): void
    {
        $department = Department::factory()->create();
        PetitionCategory::factory()->create(['name' => 'AWB', 'department_id' => $department->id]);

        $this->artisan('petitions:reassign-categories')
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Run with --commit to apply the changes.')
            ->assertSuccessful();
    }

    public function testDryRunWithBothCategoriesAndPetitions(): void
    {
        $department = Department::factory()->create();
        $fromCategory = $this->createFromCategory('AWB', $department);
        $toCategory = $this->createToCategory('Awb', $department);

        Petition::factory()->create([
            'department_id' => $department->id,
            'petition_category_id' => $fromCategory->id,
        ]);

        $this->artisan('petitions:reassign-categories')
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Run with --commit to apply the changes.')
            ->assertSuccessful();

        $this->assertDatabaseHas(Petition::class, ['petition_category_id' => $fromCategory->id]);
        $this->assertDatabaseMissing(Petition::class, ['petition_category_id' => $toCategory->id]);
    }

    public function testCommitFailsWhenToCategoryMissing(): void
    {
        $department = Department::factory()->create();
        $this->createFromCategory('AWB', $department);

        $this->artisan('petitions:reassign-categories', ['--commit' => true])
            ->expectsOutput('Some mappings could not be resolved. Fix the issues above before committing.')
            ->assertFailed();
    }

    public function testCommitSuccessfully(): void
    {
        $department = Department::factory()->create();
        $this->createAllCategoryMappings($department);

        $fromCategory = PetitionCategory::query()->withoutGlobalScopes()
            ->where('name', 'AWB')->where('department_id', $department->id)->first();
        $toCategory = PetitionCategory::query()->withoutGlobalScopes()
            ->where('name', 'Awb')->where('department_id', $department->id)->first();

        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'petition_category_id' => $fromCategory->id,
        ]);

        $this->artisan('petitions:reassign-categories', ['--commit' => true])
            ->expectsOutputToContain('Successfully reassigned 1 petition(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'petition_category_id' => $toCategory->id,
        ]);
    }

    public function testCommitHandlesDbException(): void
    {
        $department = Department::factory()->create();
        $this->createFromCategory('AWB', $department);
        $this->createToCategory('Awb', $department);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION raise_reassign_categories_test_exception()
            RETURNS TRIGGER AS $$
            BEGIN
                RAISE EXCEPTION 'Simulated DB error for testing';
            END;
            $$ LANGUAGE plpgsql
            SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER test_fail_petitions_update
            BEFORE UPDATE ON petitions
            FOR EACH STATEMENT EXECUTE FUNCTION raise_reassign_categories_test_exception()
            SQL);

        $this->artisan('petitions:reassign-categories', ['--commit' => true])
            ->expectsOutputToContain('Error reassigning categories:')
            ->assertFailed();
    }

    private function createFromCategory(string $name, Department $department): PetitionCategory
    {
        return PetitionCategory::factory()->create(['name' => $name, 'department_id' => $department->id]);
    }

    private function createToCategory(string $name, Department $department): PetitionCategory
    {
        return PetitionCategory::factory()->create(['name' => $name, 'department_id' => $department->id]);
    }

    private function createAllCategoryMappings(Department $department): void
    {
        $toNames = array_unique(array_values($this->mappings));

        foreach ($toNames as $name) {
            PetitionCategory::factory()->create(['name' => $name, 'department_id' => $department->id]);
        }

        foreach (array_keys($this->mappings) as $name) {
            PetitionCategory::factory()->create(['name' => $name, 'department_id' => $department->id]);
        }
    }
}
