<?php

declare(strict_types=1);

namespace Tests\Feature\Commands\Petition;

use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use Exception;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Feature\FeatureTestCase;

use function collect;
use function file_exists;
use function file_put_contents;
use function storage_path;
use function unlink;

class LinkCategoriesFromExcelCommandTest extends FeatureTestCase
{
    protected string $testFilePath = 'test-link-categories.xlsx';

    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists(storage_path('app/' . $this->testFilePath))) {
            unlink(storage_path('app/' . $this->testFilePath));
        }
    }

    public function testFileNotFound(): void
    {
        $this->artisan('petitions:link-categories-from-excel', [
            'file' => '/nonexistent/path/to/file.xlsx',
        ])
            ->expectsOutput('File not found: /nonexistent/path/to/file.xlsx')
            ->assertFailed();
    }

    public function testEmptyFile(): void
    {
        file_put_contents(storage_path('app/' . $this->testFilePath), 'content');

        Excel::shouldReceive('toCollection')->andReturn(collect([collect()]));

        $this->artisan('petitions:link-categories-from-excel', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Excel file is empty')
            ->assertFailed();
    }

    public function testExcelReadError(): void
    {
        file_put_contents(storage_path('app/' . $this->testFilePath), 'content');

        Excel::shouldReceive('toCollection')->andThrow(new Exception('Could not read file'));

        $this->artisan('petitions:link-categories-from-excel', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Error reading Excel file: Could not read file')
            ->assertFailed();
    }

    public function testInvalidHeaderRow(): void
    {
        file_put_contents(storage_path('app/' . $this->testFilePath), 'content');

        Excel::shouldReceive('toCollection')->andReturn(collect([collect(['not-a-collection'])]));

        $this->artisan('petitions:link-categories-from-excel', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Excel file has an invalid header row')
            ->assertFailed();
    }

    public function testMissingRequiredColumns(): void
    {
        $this->createExcelFile([
            ['Zaaknummer', 'Verkeerde kolom'],
            ['2025000005', 'Some Category'],
        ]);

        $this->artisan('petitions:link-categories-from-excel', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('Excel file must contain the "Zaaknummer" and "Categorie" columns.')
            ->assertFailed();
    }

    public function testDryRunWithNoMatchingPetitions(): void
    {
        $this->createExcelFile([
            ['Zaaknummer', 'Categorie'],
            ['2025000005', 'Subsidieregeling Stagefonds Zorg (2023-2024)'],
        ]);

        $this->artisan('petitions:link-categories-from-excel', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Dry run completed. Would link 0 petition(s).')
            ->expectsOutput('Run with --commit to apply the changes.')
            ->assertSuccessful();
    }

    public function testDryRunWithValidData(): void
    {
        ['petition' => $petition, 'category' => $category] = $this->createPetitionWithCategory(
            '2025000005',
            'Subsidieregeling Stagefonds Zorg (2023-2024)',
        );

        $this->createExcelFile([
            ['Zaaknummer', 'Categorie'],
            ['2025000005', 'Subsidieregeling Stagefonds Zorg (2023-2024)'],
        ]);

        $this->artisan('petitions:link-categories-from-excel', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Dry run completed. Would link 1 petition(s).')
            ->expectsOutput('Run with --commit to apply the changes.')
            ->assertSuccessful();

        $this->assertDatabaseMissing(Petition::class, [
            'id' => $petition->id,
            'petition_category_id' => $category->id,
        ]);
    }

    public function testDryRunSkipsEmptyRows(): void
    {
        $department = Department::factory()->create();
        PetitionCategory::factory()->create([
            'name' => 'Subsidieregeling Stagefonds Zorg (2023-2024)',
            'department_id' => $department->id,
        ]);
        Petition::factory()->create(['number' => '2025000005', 'department_id' => $department->id]);
        Petition::factory()->create(['number' => '2025000009', 'department_id' => $department->id]);

        $this->createExcelFile([
            ['Zaaknummer', 'Categorie'],
            ['2025000005', 'Subsidieregeling Stagefonds Zorg (2023-2024)'],
            [null, null],
            ['2025000009', 'Subsidieregeling Stagefonds Zorg (2023-2024)'],
        ]);

        $this->artisan('petitions:link-categories-from-excel', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('Dry run completed. Would link 2 petition(s).')
            ->assertSuccessful();
    }

    public function testDryRunWithMissingPetitionNumber(): void
    {
        $this->createExcelFile([
            ['Zaaknummer', 'Categorie'],
            [null, 'Subsidieregeling Stagefonds Zorg (2023-2024)'],
        ]);

        $this->artisan('petitions:link-categories-from-excel', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Dry run completed. Would link 0 petition(s).')
            ->assertSuccessful();
    }

    public function testDryRunWithMissingCategoryName(): void
    {
        $this->createExcelFile([
            ['Zaaknummer', 'Categorie'],
            ['2025000005', null],
        ]);

        $this->artisan('petitions:link-categories-from-excel', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Dry run completed. Would link 0 petition(s).')
            ->assertSuccessful();
    }

    public function testCommitWithValidData(): void
    {
        ['petition' => $petition, 'category' => $category] = $this->createPetitionWithCategory(
            '2025000005',
            'Subsidieregeling Stagefonds Zorg (2023-2024)',
        );

        $this->createExcelFile([
            ['Zaaknummer', 'Categorie'],
            ['2025000005', 'Subsidieregeling Stagefonds Zorg (2023-2024)'],
        ]);

        $this->artisan('petitions:link-categories-from-excel', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->expectsOutput('Successfully linked 1 petition(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'petition_category_id' => $category->id,
        ]);
    }

    public function testCommitNoChangeNeededWhenAlreadyAssigned(): void
    {
        $department = Department::factory()->create();
        $category = PetitionCategory::factory()->create([
            'name' => 'Subsidieregeling Stagefonds Zorg (2023-2024)',
            'department_id' => $department->id,
        ]);
        $petition = Petition::factory()->create([
            'number' => '2025000005',
            'department_id' => $department->id,
        ]);
        Petition::query()->where('id', $petition->id)->update(['petition_category_id' => $category->id]);

        $this->createExcelFile([
            ['Zaaknummer', 'Categorie'],
            ['2025000005', 'Subsidieregeling Stagefonds Zorg (2023-2024)'],
        ]);

        $this->artisan('petitions:link-categories-from-excel', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->expectsOutput('Successfully linked 0 petition(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'petition_category_id' => $category->id,
        ]);
    }

    public function testCommitSkipsEmptyRows(): void
    {
        ['petition' => $petition, 'category' => $category] = $this->createPetitionWithCategory(
            '2025000005',
            'Subsidieregeling Stagefonds Zorg (2023-2024)',
        );

        $this->createExcelFile([
            ['Zaaknummer', 'Categorie'],
            ['2025000005', 'Subsidieregeling Stagefonds Zorg (2023-2024)'],
            [null, null],
        ]);

        $this->artisan('petitions:link-categories-from-excel', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->expectsOutput('Successfully linked 1 petition(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'petition_category_id' => $category->id,
        ]);
    }

    public function testCommitSkipsFullyEmptyCollectionRowsFromExcel(): void
    {
        ['petition' => $petition, 'category' => $category] = $this->createPetitionWithCategory(
            '2025000011',
            'Subsidieregeling Stagefonds Zorg (2023-2024)',
        );

        file_put_contents(storage_path('app/' . $this->testFilePath), 'content');

        Excel::shouldReceive('toCollection')->andReturn(collect([
            collect([
                collect(['Zaaknummer', 'Categorie']),
                collect(['2025000011', 'Subsidieregeling Stagefonds Zorg (2023-2024)']),
                collect([null, '']),
            ]),
        ]));

        $this->artisan('petitions:link-categories-from-excel', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->expectsOutput('Successfully linked 1 petition(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'petition_category_id' => $category->id,
        ]);
    }

    public function testCommitFailsWhenPetitionNotFound(): void
    {
        $this->createExcelFile([
            ['Zaaknummer', 'Categorie'],
            ['9999999999', 'Subsidieregeling Stagefonds Zorg (2023-2024)'],
        ]);

        $this->artisan('petitions:link-categories-from-excel', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->expectsOutput('Some petitions or categories could not be resolved. Fix the issues above before committing.')
            ->assertFailed();
    }

    public function testCommitFailsWhenCategoryNotFoundInDepartment(): void
    {
        $department = Department::factory()->create();
        Petition::factory()->create(['number' => '2025000005', 'department_id' => $department->id]);

        $this->createExcelFile([
            ['Zaaknummer', 'Categorie'],
            ['2025000005', 'Nonexistent Category'],
        ]);

        $this->artisan('petitions:link-categories-from-excel', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->expectsOutput('Some petitions or categories could not be resolved. Fix the issues above before committing.')
            ->assertFailed();
    }

    public function testCommitHandlesDbException(): void
    {
        $this->createPetitionWithCategory('2025000005', 'Subsidieregeling Stagefonds Zorg (2023-2024)');

        $this->createExcelFile([
            ['Zaaknummer', 'Categorie'],
            ['2025000005', 'Subsidieregeling Stagefonds Zorg (2023-2024)'],
        ]);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION raise_link_categories_test_exception()
            RETURNS TRIGGER AS $$
            BEGIN
                RAISE EXCEPTION 'Simulated DB error for testing';
            END;
            $$ LANGUAGE plpgsql
            SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER test_fail_link_categories_update
            BEFORE UPDATE ON petitions
            FOR EACH STATEMENT EXECUTE FUNCTION raise_link_categories_test_exception()
            SQL);

        $this->artisan('petitions:link-categories-from-excel', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->expectsOutputToContain('Error linking categories:')
            ->assertFailed();
    }

    /**
     * @return array{petition: Petition, category: PetitionCategory, department: Department}
     */
    private function createPetitionWithCategory(string $number, string $categoryName): array
    {
        $department = Department::factory()->create();
        $category = PetitionCategory::factory()->create([
            'name' => $categoryName,
            'department_id' => $department->id,
        ]);
        $petition = Petition::factory()->create([
            'number' => $number,
            'department_id' => $department->id,
        ]);

        return ['petition' => $petition, 'category' => $category, 'department' => $department];
    }

    /**
     * @param array<int, array<int, int|string|null>> $data
     */
    private function createExcelFile(array $data): void
    {
        $filePath = storage_path('app/' . $this->testFilePath);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($data as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 1], $value);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
        $spreadsheet->disconnectWorksheets();
    }
}
