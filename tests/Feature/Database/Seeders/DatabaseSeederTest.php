<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders;

use App\Enums\PetitionVariant;
use App\Models\Department;
use Database\Seeders\DatabaseSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class DatabaseSeederTest extends FeatureTestCase
{
    #[Test]
    public function testCreatePetitionTypesCreatesOneTypePerPetitionVariant(): void
    {
        $department = Department::factory()->create();

        $petitionTypes = $this->app->make(DatabaseSeeder::class)->createPetitionTypes($department);

        $this->assertEqualsCanonicalizing(
            PetitionVariant::cases(),
            $petitionTypes->pluck('type')->all(),
        );
    }
}
