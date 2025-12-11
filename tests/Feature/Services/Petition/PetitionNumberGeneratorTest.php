<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Petition;

use App\Models\Department;
use App\Models\PetitionNumber;
use App\Services\Petition\PetitionNumberGenerator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function sprintf;
use function substr;

class PetitionNumberGeneratorTest extends FeatureTestCase
{
    #[Test]
    public function testNumberIsGeneratedAfterTheLastPetition(): void
    {
        $department = Department::factory()->create();

        $today = CarbonImmutable::create($this->faker->date());
        $year = $today->format('Y');
        $existingNumber = $this->faker->numberBetween(100, 998); // taking 3 digits here for test-convenience

        PetitionNumber::factory()->create([
            'department_id' => $department->id,
            'year' => $year,
            'number' => $existingNumber,
        ]);

        CarbonImmutable::setTestNow($today);

        $petitionNumberGenerator = $this->app->get(PetitionNumberGenerator::class);

        $expectedNumber = sprintf('%s%s00%s', $today->format('Y'), substr($department->abbreviation, 0, 1), $existingNumber + 1);
        $this->assertEquals($expectedNumber, $petitionNumberGenerator->generate($department));
    }

    #[Test]
    public function testNumberIsGeneratedAndOthersNot(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $today = CarbonImmutable::create($this->faker->date());
        $year = $today->format('Y');
        $existingNumber = $this->faker->numberBetween(100, 999); // taking 3 digits here for test-convenience

        PetitionNumber::factory()->count(2)
            ->sequence(
                [
                    'department_id' => $department1->id,
                ],
                [
                    'department_id' => $department2->id,
                ],
            )->create([
                'year' => $year,
                'number' => $existingNumber,
            ]);

        CarbonImmutable::setTestNow($today);

        $petitionNumberGenerator = $this->app->get(PetitionNumberGenerator::class);

        $petitionNumberGenerator->generate($department2);
        $this->assertDatabaseHas(PetitionNumber::class, [
            'department_id' => $department1->id,
            'year' => $year,
            'number' => $existingNumber,
        ]);
        $this->assertDatabaseHas(PetitionNumber::class, [
            'department_id' => $department2->id,
            'year' => $year,
            'number' => $existingNumber + 1,
        ]);
    }
}
