<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Models\Department;
use App\Models\Petition;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class UpdatePetitionNumberCommandTest extends FeatureTestCase
{
    #[Test]
    public function testCommandFailsWhenFromOptionIsMissing(): void
    {
        $this->artisan('petitions:update-number', ['--to' => '2025000547'])
            ->expectsOutput('Both --from and --to options are required.')
            ->assertFailed();
    }

    #[Test]
    public function testCommandFailsWhenToOptionIsMissing(): void
    {
        $this->artisan('petitions:update-number', ['--from' => '2025C00041'])
            ->expectsOutput('Both --from and --to options are required.')
            ->assertFailed();
    }

    #[Test]
    public function testCommandFailsWhenFromAndToAreIdentical(): void
    {
        $this->artisan('petitions:update-number', ['--from' => '2025C00041', '--to' => '2025C00041'])
            ->expectsOutput('--from and --to are identical; nothing to update.')
            ->assertFailed();
    }

    #[Test]
    public function testCommandFailsWhenPetitionNotFound(): void
    {
        $this->artisan('petitions:update-number', ['--from' => 'DOES-NOT-EXIST', '--to' => '2025000547'])
            ->expectsOutput('No petition found with number "DOES-NOT-EXIST".')
            ->assertFailed();
    }

    #[Test]
    public function testCommandShowsPreviewInDryRunMode(): void
    {
        $petition = $this->createPetition('2025C00041');

        $this->artisan('petitions:update-number', ['--from' => '2025C00041', '--to' => '2025000547'])
            ->expectsOutputToContain('DRY RUN MODE')
            ->expectsOutputToContain('2025C00041')
            ->expectsOutputToContain('2025000547')
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', ['id' => $petition->id, 'number' => '2025C00041']);
    }

    #[Test]
    public function testCommandDoesNotPersistChangesInDryRunMode(): void
    {
        $this->createPetition('2025C00041');

        $this->artisan('petitions:update-number', ['--from' => '2025C00041', '--to' => '2025000547'])
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', ['number' => '2025C00041']);
        $this->assertDatabaseMissing('petitions', ['number' => '2025000547']);
    }

    #[Test]
    public function testCommandUpdatesPetitionNumberWithCommit(): void
    {
        $petition = $this->createPetition('2025C00041');

        $this->artisan('petitions:update-number', [
            '--from' => '2025C00041',
            '--to' => '2025000547',
            '--commit' => true,
        ])
            ->expectsOutputToContain('Successfully updated petition number')
            ->assertSuccessful();

        $this->assertDatabaseHas('petitions', ['id' => $petition->id, 'number' => '2025000547']);
        $this->assertDatabaseMissing('petitions', ['number' => '2025C00041']);
    }

    #[Test]
    public function testCommandOnlyUpdatesMatchingPetition(): void
    {
        $this->createPetition('2025C00041');
        $other = $this->createPetition('2025C00011');

        $this->artisan('petitions:update-number', [
            '--from' => '2025C00041',
            '--to' => '2025000547',
            '--commit' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('petitions', ['id' => $other->id, 'number' => '2025C00011']);
    }

    private function createPetition(string $number): Petition
    {
        $department = Department::factory()->create();

        return Petition::factory()->create([
            'department_id' => $department->id,
            'number' => $number,
        ]);
    }
}
