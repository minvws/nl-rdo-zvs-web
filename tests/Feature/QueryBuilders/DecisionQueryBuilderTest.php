<?php

declare(strict_types=1);

namespace Tests\Feature\QueryBuilders;

use App\Models\Decision;
use Tests\Feature\FeatureTestCase;

use function now;

class DecisionQueryBuilderTest extends FeatureTestCase
{
    public function testDecisionQueryBuilder(): void
    {
        Decision::factory()->create(['archived_at' => null]);
        Decision::factory()->create(['archived_at' => now()]);

        $decisions = Decision::query()->notArchived()->get();
        $this->assertCount(1, $decisions);
        $this->assertNull($decisions->first()->archived_at);
    }
}
