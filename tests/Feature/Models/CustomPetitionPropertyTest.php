<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\CustomPetitionProperty;
use App\Models\Petition;
use App\Models\PetitionType;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class CustomPetitionPropertyTest extends FeatureTestCase
{
    #[Test]
    public function testPetitionsRelationship(): void
    {
        $customProperty = CustomPetitionProperty::factory()->create();
        $petitions = Petition::factory()->count(2)->create();

        $customProperty->petitions()->attach($petitions->pluck('id')->toArray());

        $this->assertEquals(2, $customProperty->petitions->count());
        $this->assertContainsOnlyInstancesOf(Petition::class, $customProperty->petitions);
    }

    #[Test]
    public function testPetitionTypeRelationship(): void
    {
        $petitionType = PetitionType::factory()->create();
        $customProperty = CustomPetitionProperty::factory()->create([
            'petition_type_id' => $petitionType->id,
        ]);

        $this->assertInstanceOf(PetitionType::class, $customProperty->petitionType);
        $this->assertEquals($petitionType->id, $customProperty->petitionType->id);
    }
}
