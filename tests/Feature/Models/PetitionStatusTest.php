<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class PetitionStatusTest extends FeatureTestCase
{
    #[Test]
    public function testPetitionsRelationship(): void
    {
        $petitionStatus = PetitionStatus::factory()->create();
        Petition::factory()->recycle($petitionStatus)->count(3)->create();

        $this->assertEquals(3, $petitionStatus->petitions->count());
        $this->assertContainsOnlyInstancesOf(Petition::class, $petitionStatus->petitions);
    }

    #[Test]
    public function testPetitionStatusHistoriesRelationship(): void
    {
        $petitionStatus = PetitionStatus::factory()->create();
        PetitionStatusHistory::factory()->recycle($petitionStatus)->count(2)->create();

        $this->assertEquals(2, $petitionStatus->petitionStatusHistories->count());
        $this->assertContainsOnlyInstancesOf(PetitionStatusHistory::class, $petitionStatus->petitionStatusHistories);
    }
}
