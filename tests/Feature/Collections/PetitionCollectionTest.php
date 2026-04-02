<?php

declare(strict_types=1);

namespace Tests\Feature\Collections;

use App\Models\Petition;
use Tests\Feature\FeatureTestCase;

use function sprintf;

class PetitionCollectionTest extends FeatureTestCase
{
    public function testToString(): void
    {
        $petition1 = Petition::factory()->create();
        $petition2 = Petition::factory()->create();

        $petitions = Petition::orderBy('id')->get();

        $this->assertEquals(sprintf('%s, %s', $petition1->number, $petition2->number), $petitions->toString());
    }
}
