<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\CustomDateLabel;
use App\Models\PetitionType;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class PetitionTypeCustomDateLabelTest extends FeatureTestCase
{
    #[Test]
    public function testPetitionTypeRelationship(): void
    {
        $petitionType = PetitionType::factory()->create();

        $customDateLabel = $petitionType->customDateLabels()->create(['date_label' => CustomDateLabel::DATE_RULING]);

        $this->assertInstanceOf(PetitionType::class, $customDateLabel->petitionType);
        $this->assertEquals($petitionType->id, $customDateLabel->petitionType->id);
    }
}
