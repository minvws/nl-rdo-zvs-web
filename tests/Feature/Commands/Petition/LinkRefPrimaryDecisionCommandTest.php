<?php

declare(strict_types=1);

namespace Tests\Feature\Commands\Petition;

use App\Enums\ContactRole;
use App\Models\Contact;
use App\Models\ContactPetition;
use App\Models\Petition;
use Exception;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Feature\FeatureTestCase;

use function collect;
use function file_exists;
use function file_put_contents;
use function sprintf;
use function storage_path;
use function unlink;

class LinkRefPrimaryDecisionCommandTest extends FeatureTestCase
{
    protected string $testFilePath = 'test-primary-decision.xlsx';

    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists(storage_path('app/' . $this->testFilePath))) {
            unlink(storage_path('app/' . $this->testFilePath));
        }
    }

    public function testDryRunWithValidData(): void
    {
        $petition = Petition::factory()->create(['message' => '']);
        $primaryDecisionReference = 'REF-2024-001';

        $this->createExcelFile([
            ['Zaaknummers', 'Kenmerk Primair Besluit'],
            [$petition->number, $primaryDecisionReference],
        ]);

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput(
                sprintf('Would update petition %s with primary decision reference "%s"', $petition->number, $primaryDecisionReference),
            )
            ->expectsOutput('  Would update 0 applicant link(s)')
            ->expectsOutput('Dry run completed. Would update 1 petition(s) and 0 applicant link(s).')
            ->assertSuccessful();

        $this->assertDatabaseMissing(Petition::class, [
            'id' => $petition->id,
            'message' => $primaryDecisionReference,
        ]);
    }

    public function testDryRunWithApplicantLinks(): void
    {
        $petition = Petition::factory()->create(['message' => '']);
        $contact = Contact::factory()->create();

        ContactPetition::query()->insert([
            'petition_id' => $petition->id,
            'contact_id' => $contact->id,
            'role' => ContactRole::APPLICANT->value,
        ]);

        $primaryDecisionReference = 'REF-2024-001';

        $this->createExcelFile([
            ['Zaaknummers', 'Kenmerk Primair Besluit'],
            [$petition->number, $primaryDecisionReference],
        ]);

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput(
                sprintf('Would update petition %s with primary decision reference "%s"', $petition->number, $primaryDecisionReference),
            )
            ->expectsOutput('  Would update 1 applicant link(s)')
            ->expectsOutput('Dry run completed. Would update 1 petition(s) and 1 applicant link(s).')
            ->assertSuccessful();

        $this->assertDatabaseMissing(ContactPetition::class, [
            'petition_id' => $petition->id,
            'reference' => $primaryDecisionReference,
        ]);
    }

    public function testCommitWithValidData(): void
    {
        $petition = Petition::factory()->create(['message' => '']);
        $primaryDecisionReference = 'REF-2024-001';

        $this->createExcelFile([
            ['Zaaknummers', 'Kenmerk Primair Besluit'],
            [$petition->number, $primaryDecisionReference],
        ]);

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->expectsOutput('Successfully updated 1 petition(s) and 0 applicant link(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'message' => $primaryDecisionReference,
        ]);
    }

    public function testCommitWithMultipleApplicantLinks(): void
    {
        $petition = Petition::factory()->create(['message' => '']);
        $contact1 = Contact::factory()->create();
        $contact2 = Contact::factory()->create();
        $otherContact = Contact::factory()->create();

        ContactPetition::query()->insert([
            [
                'petition_id' => $petition->id,
                'contact_id' => $contact1->id,
                'role' => ContactRole::APPLICANT->value,
            ],
            [
                'petition_id' => $petition->id,
                'contact_id' => $contact2->id,
                'role' => ContactRole::APPLICANT->value,
            ],
            [
                'petition_id' => $petition->id,
                'contact_id' => $otherContact->id,
                'role' => ContactRole::REPRESENTATIVE->value,
            ],
        ]);

        $primaryDecisionReference = 'REF-2024-001';

        $this->createExcelFile([
            ['Zaaknummers', 'Kenmerk Primair Besluit'],
            [$petition->number, $primaryDecisionReference],
        ]);

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->expectsOutput('Successfully updated 1 petition(s) and 2 applicant link(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'message' => $primaryDecisionReference,
        ]);

        $this->assertDatabaseHas(ContactPetition::class, [
            'petition_id' => $petition->id,
            'contact_id' => $contact1->id,
            'role' => ContactRole::APPLICANT->value,
            'reference' => $primaryDecisionReference,
        ]);

        $this->assertDatabaseHas(ContactPetition::class, [
            'petition_id' => $petition->id,
            'contact_id' => $contact2->id,
            'role' => ContactRole::APPLICANT->value,
            'reference' => $primaryDecisionReference,
        ]);

        $this->assertDatabaseMissing(ContactPetition::class, [
            'petition_id' => $petition->id,
            'contact_id' => $otherContact->id,
            'role' => ContactRole::REPRESENTATIVE->value,
            'reference' => $primaryDecisionReference,
        ]);
    }

    public function testCommitWithMultiplePetitions(): void
    {
        $petition1 = Petition::factory()->create(['message' => '']);
        $petition2 = Petition::factory()->create(['message' => '']);
        $ref1 = 'REF-2024-001';
        $ref2 = 'REF-2024-002';

        $this->createExcelFile([
            ['Zaaknummers', 'Kenmerk Primair Besluit'],
            [$petition1->number, $ref1],
            [$petition2->number, $ref2],
        ]);

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->expectsOutput('Successfully updated 2 petition(s) and 0 applicant link(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition1->id,
            'message' => $ref1,
        ]);

        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition2->id,
            'message' => $ref2,
        ]);
    }

    public function testFileNotFound(): void
    {
        $file = storage_path('app/nonexistent-file.xlsx');

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => $file,
        ])
            ->expectsOutput(sprintf('File not found: %s', $file))
            ->assertFailed();
    }

    public function testEmptyExcelFile(): void
    {
        // Ensure file exists and simulate Excel returning an empty sheet
        file_put_contents(storage_path('app/' . $this->testFilePath), '');
        Excel::shouldReceive('toCollection')->andReturn(collect(collect()));

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Excel file is empty')
            ->assertFailed();
    }

    public function testMissingRequiredColumns(): void
    {
        $this->createExcelFile([
            ['Column1', 'Column2'],
            ['Value1', 'Value2'],
        ]);

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('Excel file must contain the "Zaaknummers" and "Kenmerk Primair Besluit" columns.')
            ->assertFailed();
    }

    public function testMissingPetitionNumber(): void
    {
        $this->createExcelFile([
            ['Zaaknummers', 'Kenmerk Primair Besluit'],
            [null, 'REF-2024-001'],
        ]);

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Dry run completed. Would update 0 petition(s) and 0 applicant link(s).')
            ->expectsOutput('  Row 2: Missing value for column: Zaaknummers')
            ->assertSuccessful();
    }

    public function testPetitionNotFound(): void
    {
        $this->createExcelFile([
            ['Zaaknummers', 'Kenmerk Primair Besluit'],
            ['NONEXISTENT-123', 'REF-2024-001'],
        ]);

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Dry run completed. Would update 0 petition(s) and 0 applicant link(s).')
            ->expectsOutput('  Row 2: Petition not found: NONEXISTENT-123')
            ->assertSuccessful();
    }

    public function testPetitionWithExistingMessageIsSkipped(): void
    {
        $petition = Petition::factory()->create(['message' => 'Existing message']);
        $primaryDecisionReference = 'REF-2024-001';

        $this->createExcelFile([
            ['Zaaknummers', 'Kenmerk Primair Besluit'],
            [$petition->number, $primaryDecisionReference],
        ]);

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Dry run completed. Would update 0 petition(s) and 0 applicant link(s).')
            ->expectsOutput('  Row 2: Petition already has something filled in the `message` column')
            ->assertSuccessful();

        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'message' => 'Existing message',
        ]);
    }

    public function testEmptyRowsAreSkipped(): void
    {
        $petition = Petition::factory()->create(['message' => '']);
        $primaryDecisionReference = 'REF-2024-001';

        $this->createExcelFile([
            ['Zaaknummers', 'Kenmerk Primair Besluit'],
            [$petition->number, $primaryDecisionReference],
            [null, null],
            ['', ''],
            [$petition->number, $primaryDecisionReference],
        ]);

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Dry run completed. Would update 2 petition(s) and 0 applicant link(s).')
            ->assertSuccessful();
    }

    public function testWhitespaceIsTrimmed(): void
    {
        $petition = Petition::factory()->create(['message' => '']);
        $primaryDecisionReference = 'REF-2024-001';

        $this->createExcelFile([
            ['Zaaknummers', 'Kenmerk Primair Besluit'],
            ['  ' . $petition->number . '  ', '  ' . $primaryDecisionReference . '  '],
        ]);

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->expectsOutput('Successfully updated 1 petition(s) and 0 applicant link(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'message' => $primaryDecisionReference,
        ]);
    }

    public function testColumnNameVariations(): void
    {
        $petition = Petition::factory()->create(['message' => '']);
        $primaryDecisionReference = 'REF-2024-001';

        $this->createExcelFile([
            ['ZAAKNUMMERS', 'Kenmerk Primair Besluit'],
            [$petition->number, $primaryDecisionReference],
        ]);

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Dry run completed. Would update 1 petition(s) and 0 applicant link(s).')
            ->assertSuccessful();
    }

    public function testMultipleFailures(): void
    {
        $validPetition = Petition::factory()->create(['message' => '']);

        $this->createExcelFile([
            ['Zaaknummers', 'Kenmerk Primair Besluit'],
            [$validPetition->number, 'REF-2024-001'],
            [null, 'REF-2024-002'],
            ['NONEXISTENT', 'REF-2024-003'],
            ['', 'REF-2024-004'],
        ]);

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Dry run completed. Would update 1 petition(s) and 0 applicant link(s).')
            ->assertSuccessful();
    }

    public function testUnreadableExcelFile(): void
    {
        // Ensure file exists so the command advances to reading the file
        file_put_contents(storage_path('app/' . $this->testFilePath), 'corrupt');

        // Simulate Excel facade throwing when reading an unreadable/corrupted file
        Excel::shouldReceive('toCollection')->andThrow(new Exception('Could not read file'));

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput(sprintf('Error reading Excel file: %s', 'Could not read file'))
            ->assertFailed();
    }

    public function testInvalidHeaderRow(): void
    {
        // Ensure file exists so the command advances to reading the file
        file_put_contents(storage_path('app/' . $this->testFilePath), 'content');

        // Simulate Excel returning a sheet whose first row is not an array
        Excel::shouldReceive('toCollection')
            ->andReturn(collect([collect(['not-an-array'])]));

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => storage_path('app/' . $this->testFilePath),
        ])
            ->expectsOutput('DRY RUN MODE - No changes will be made to the database')
            ->expectsOutput('Excel file has an invalid header row')
            ->assertFailed();
    }

    public function testEmptyPrimaryDecisionReferenceIsAllowed(): void
    {
        $petition = Petition::factory()->create();
        $petition->update(['message' => 'Old Value']);

        $this->createExcelFile([
            ['Zaaknummers', 'Kenmerk Primair Besluit'],
            [$petition->number, ''],
        ]);

        $this->artisan('petitions:link-ref-primary-decision', [
            'file' => storage_path('app/' . $this->testFilePath),
            '--commit' => true,
        ])
            ->expectsOutput('Successfully updated 0 petition(s) and 0 applicant link(s).')
            ->expectsOutput('  Row 2: Missing value for column: Kenmerk Primair Besluit')
            ->assertSuccessful();

        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'message' => 'Old Value', //unchanged
        ]);
    }

    /**
     * @param array<int, array<int, string|null>> $data
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
