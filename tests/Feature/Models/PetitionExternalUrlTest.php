<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Petition;
use App\Models\PetitionExternalUrl;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class PetitionExternalUrlTest extends FeatureTestCase
{
    #[Test]
    public function testPetitionRelationship(): void
    {
        $petition = Petition::factory()->create();
        $externalUrl = PetitionExternalUrl::factory()->create([
            'petition_id' => $petition->id,
        ]);

        $this->assertInstanceOf(Petition::class, $externalUrl->petition);
        $this->assertEquals($petition->id, $externalUrl->petition->id);
    }
}
