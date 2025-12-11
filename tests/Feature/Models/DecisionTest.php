<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Decision;
use App\Models\Petition;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class DecisionTest extends FeatureTestCase
{
    #[Test]
    public function testCreateModelAndSyncRelation(): void
    {
        $decision = Decision::factory()->create();
        $petition = Petition::factory()->create();

        $decision->petitions()->sync([$petition->id->toString()]);

        $this->assertDatabaseHas('decision_petition', [
            'decision_id' => $decision->id,
            'petition_id' => $petition->id,
        ]);
    }
}
