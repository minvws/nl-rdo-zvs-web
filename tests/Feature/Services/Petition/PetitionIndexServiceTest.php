<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Petition;

use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionType;
use App\Services\Petition\PetitionIndexService;
use Tests\Feature\FeatureTestCase;

class PetitionIndexServiceTest extends FeatureTestCase
{
    public function testUsedPetitionParticularitiesAreSortedAndIncludeCalculatedLabels(): void
    {
        $department = Department::factory()->create();

        $alphaType = PetitionType::factory()->recycle($department)->create([
            'particularity_label' => 'Zulu',
        ]);
        Petition::factory()->recycle($department)->recycle($alphaType)->create();

        $betaType = PetitionType::factory()->recycle($department)->create([
            'particularity_label' => 'Alpha',
        ]);
        Petition::factory()->recycle($department)->recycle($betaType)->create();

        $particularities = $this->app->make(PetitionIndexService::class)
            ->getUsedPetitionParticularities($department)
            ->all();

        $this->assertSame(['Aanh', 'Alpha', 'IGS', 'Opsch', 'Zulu'], $particularities);
    }
}
