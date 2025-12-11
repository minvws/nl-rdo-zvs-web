<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\CustomCost;
use App\Models\Petition;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class CustomCostTest extends FeatureTestCase
{
    #[Test]
    public function testPetitionRelationship(): void
    {
        $petition = Petition::factory()->create();

        $customCost = CustomCost::factory()->recycle($petition)->create();

        $this->assertInstanceOf(CustomCost::class, $customCost);
        $this->assertTrue($petition->is($customCost->petition));
        $this->assertInstanceOf(Petition::class, $customCost->petition);
    }
}
