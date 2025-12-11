<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PetitionTypeType;
use App\Enums\ResultType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function __;

class ResultTypeTest extends TestCase
{
    #[Test]
    public function testFinalDecisionLabel(): void
    {
        $label = ResultType::FINAL_DECISION->label();

        $this->assertEquals(__('result_type.final_decision'), $label);
    }

    #[Test]
    public function testWithdrawnLabel(): void
    {
        $label = ResultType::WITHDRAWN->label();

        $this->assertEquals(__('result_type.withdrawn'), $label);
    }

    #[Test]
    public function testForwardedLabel(): void
    {
        $label = ResultType::FORWARDED->label();

        $this->assertEquals(__('result_type.forwarded'), $label);
    }

    #[Test]
    public function testAllCasesHaveLabels(): void
    {
        foreach (ResultType::cases() as $type) {
            $this->assertNotEmpty($type->label());
        }
    }

    #[Test]
    public function testGetForPetitionTypeBezwaar(): void
    {
        $types = ResultType::getForPetitionType(PetitionTypeType::BEZWAAR);

        $this->assertCount(3, $types);
        $this->assertContains(ResultType::FINAL_DECISION, $types);
        $this->assertContains(ResultType::WITHDRAWN, $types);
        $this->assertContains(ResultType::FORWARDED, $types);
    }

    #[Test]
    public function testGetForPetitionTypeWooVerzoek(): void
    {
        $types = ResultType::getForPetitionType(PetitionTypeType::WOO_VERZOEK);

        $this->assertCount(8, $types);
        $this->assertContains(ResultType::FINAL_DECISION, $types);
        $this->assertContains(ResultType::WITHDRAWN, $types);
        $this->assertContains(ResultType::FORWARDED, $types);
        $this->assertContains(ResultType::REJECTED, $types);
        $this->assertContains(ResultType::DISMISSED, $types);
        $this->assertContains(ResultType::RECONSIDERED, $types);
        $this->assertContains(ResultType::ALREADY_PUBLIC, $types);
        $this->assertContains(ResultType::OTHER, $types);
    }

    #[Test]
    public function testGetForPetitionTypeBeroep(): void
    {
        $types = ResultType::getForPetitionType(PetitionTypeType::BEROEP);

        $this->assertEmpty($types);
    }
}
