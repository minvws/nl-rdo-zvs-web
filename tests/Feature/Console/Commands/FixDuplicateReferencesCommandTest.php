<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Models\Decision;
use App\Models\Department;
use Exception;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class FixDuplicateReferencesCommandTest extends FeatureTestCase
{
    #[Test]
    public function testGeneratesReferenceForNullValues(): void
    {
        $department = Department::factory()->create();

        $decision1 = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => null,
            'created_at' => '2025-01-01 10:00:00',
        ]);

        $decision2 = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => null,
            'created_at' => '2025-01-01 11:00:00',
        ]);

        $this->artisan('app:decisions:fix-references')
            ->assertSuccessful();

        $this->assertSame('_zvs_gegenereerde_id_00001', $this->getReferenceFor($decision1));
        $this->assertSame('_zvs_gegenereerde_id_00002', $this->getReferenceFor($decision2));
    }

    #[Test]
    public function testGeneratesReferenceForEmptyStrings(): void
    {
        $department = Department::factory()->create();

        $decision1 = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => '',
            'created_at' => '2025-01-01 10:00:00',
        ]);

        $decision2 = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => '',
            'created_at' => '2025-01-01 11:00:00',
        ]);

        $this->artisan('app:decisions:fix-references')
            ->assertSuccessful();

        $this->assertSame('_zvs_gegenereerde_id_00001', $this->getReferenceFor($decision1));
        $this->assertSame('_zvs_gegenereerde_id_00002', $this->getReferenceFor($decision2));
    }

    #[Test]
    public function testTreatsWhitespaceOnlyReferenceAsEmpty(): void
    {
        $department = Department::factory()->create();

        $decision1 = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => '   ',
            'created_at' => '2025-01-01 10:00:00',
        ]);

        $decision2 = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => '        ',
            'created_at' => '2025-01-01 10:00:01',
        ]);

        $this->artisan('app:decisions:fix-references')
            ->assertSuccessful();

        $this->assertSame('_zvs_gegenereerde_id_00001', $this->getReferenceFor($decision1));
        $this->assertSame('_zvs_gegenereerde_id_00002', $this->getReferenceFor($decision2));
    }

    #[Test]
    public function testFixesCaseInsensitiveDuplicates(): void
    {
        $department = Department::factory()->create();

        $decision1 = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => 'ABC',
            'created_at' => '2025-01-01 10:00:00',
        ]);

        $decision2 = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => 'abc',
            'created_at' => '2025-01-01 11:00:00',
        ]);

        $decision3 = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => 'Abc',
            'created_at' => '2025-01-01 12:00:00',
        ]);

        $this->artisan('app:decisions:fix-references')
            ->assertSuccessful();

        $this->assertSame('abc', $this->getReferenceFor($decision1));
        $this->assertSame('abc (dubbel ingevoerd 1)', $this->getReferenceFor($decision2));
        $this->assertSame('abc (dubbel ingevoerd 2)', $this->getReferenceFor($decision3));
    }

    #[Test]
    public function testTreatsCrossDepartmentReferencesAsDuplicates(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $decision1 = Decision::factory()->create([
            'department_id' => $department1->id,
            'reference' => 'SAME-REF',
            'created_at' => '2025-01-01 10:00:00',
        ]);

        $decision2 = Decision::factory()->create([
            'department_id' => $department2->id,
            'reference' => 'SAME-REF',
            'created_at' => '2025-01-01 11:00:00',
        ]);

        $this->artisan('app:decisions:fix-references')
            ->assertSuccessful();

        $this->assertSame('same-ref', $this->getReferenceFor($decision1));
        $this->assertSame('same-ref (dubbel ingevoerd 1)', $this->getReferenceFor($decision2));
    }

    #[Test]
    public function testHandlesMixedNullAndDuplicates(): void
    {
        $department = Department::factory()->create();

        $nullDecision = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => null,
            'created_at' => '2025-01-01 10:00:00',
        ]);

        $decision2 = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => 'ABC',
            'created_at' => '2025-01-01 11:00:00',
        ]);

        $decision3 = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => 'abc',
            'created_at' => '2025-01-01 12:00:00',
        ]);

        $this->artisan('app:decisions:fix-references')
            ->assertSuccessful();

        $refNull = $this->getReferenceFor($nullDecision);
        $this->assertSame('_zvs_gegenereerde_id_00001', $refNull);
        $this->assertSame('abc', $this->getReferenceFor($decision2));
        $this->assertSame('abc (dubbel ingevoerd 1)', $this->getReferenceFor($decision3));
    }

    #[Test]
    public function testIsIdempotent(): void
    {
        $department = Department::factory()->create();

        $decision1 = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => null,
            'created_at' => '2025-01-01 10:00:00',
        ]);

        $decision2 = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => null,
            'created_at' => '2025-01-01 11:00:00',
        ]);

        $this->artisan('app:decisions:fix-references')
            ->assertSuccessful();

        $ref1FirstRun = $this->getReferenceFor($decision1);
        $ref2FirstRun = $this->getReferenceFor($decision2);

        $this->artisan('app:decisions:fix-references')
            ->assertSuccessful();

        $ref1SecondRun = $this->getReferenceFor($decision1);
        $ref2SecondRun = $this->getReferenceFor($decision2);

        $this->assertSame($ref1FirstRun, $ref1SecondRun);
        $this->assertSame($ref2FirstRun, $ref2SecondRun);
    }

    #[Test]
    public function testHandlesNoDecisions(): void
    {
        $this->artisan('app:decisions:fix-references')
            ->expectsOutputToContain('No decisions found')
            ->assertSuccessful();
    }

    #[Test]
    public function testHandlesNoIssuesWithReferences(): void
    {
        $department = Department::factory()->create();

        Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => 'unique-ref-1',
        ]);

        Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => 'unique-ref-2',
        ]);

        $this->artisan('app:decisions:fix-references')
            ->expectsOutputToContain('No issues found')
            ->assertSuccessful();
    }

    #[Test]
    public function testProcessesMultipleDuplicateGroups(): void
    {
        $department = Department::factory()->create();

        $decision1 = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => 'REF1',
            'created_at' => '2025-01-01 10:00:00',
        ]);

        $decision2 = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => 'ref1',
            'created_at' => '2025-01-01 11:00:00',
        ]);

        $decision3 = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => 'REF2',
            'created_at' => '2025-01-01 12:00:00',
        ]);

        $decision4 = Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => 'ref2',
            'created_at' => '2025-01-01 13:00:00',
        ]);

        $this->artisan('app:decisions:fix-references')
            ->assertSuccessful();

        $this->assertSame('ref1', $this->getReferenceFor($decision1));
        $this->assertSame('ref1 (dubbel ingevoerd 1)', $this->getReferenceFor($decision2));
        $this->assertSame('ref2', $this->getReferenceFor($decision3));
        $this->assertSame('ref2 (dubbel ingevoerd 1)', $this->getReferenceFor($decision4));
    }

    #[Test]
    public function testReportsFailureOnDatabaseError(): void
    {
        $department = Department::factory()->create();

        Decision::factory()->create([
            'department_id' => $department->id,
            'reference' => null,
            'created_at' => '2025-01-01 10:00:00',
        ]);

        $mockDb = $this->createPartialMock(DatabaseManager::class, ['connection']);
        $mockDb->method('connection')->willReturnCallback(function (): void {
            throw new Exception('Database connection failed');
        });

        $this->app->instance(DatabaseManager::class, $mockDb);

        $this->artisan('app:decisions:fix-references')
            ->expectsOutputToContain('Database connection failed')
            ->assertExitCode(1);
    }

    private function getReferenceFor(Decision $decision): ?string
    {
        return $decision->fresh()->reference;
    }
}
