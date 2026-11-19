<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components;

use App\Config\Config;
use App\Enums\PetitionVariant;
use App\Models\Petition;
use App\Models\PetitionType;
use App\View\Components\Petition\Deadlines;
use Illuminate\Contracts\View\View;
use Tests\Feature\FeatureTestCase;

class DeadlinesTest extends FeatureTestCase
{
    public function testRendersTheDeadlinesWhenEnabled(): void
    {
        $petition = $this->petitionOfType(PetitionVariant::BEZWAAR);

        $this->assertInstanceOf(View::class, $this->makeComponent($petition)->render());
    }

    public function testRendersNothingWhenDisabled(): void
    {
        $petition = $this->petitionOfType(PetitionVariant::VERZET);

        $this->assertNull($this->makeComponent($petition)->render());
    }

    private function petitionOfType(PetitionVariant $variant): Petition
    {
        $petitionType = PetitionType::factory()->create(['type' => $variant]);

        return Petition::factory()->for($petitionType)->create();
    }

    private function makeComponent(Petition $petition): Deadlines
    {
        return new Deadlines(Config::array('petition_variant'), $petition);
    }
}
