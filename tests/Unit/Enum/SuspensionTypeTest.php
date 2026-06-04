<?php

declare(strict_types=1);

namespace Tests\Unit\Enum;

use App\Enums\PetitionVariant;
use App\Enums\SuspensionType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SuspensionTypeTest extends TestCase
{
    #[Test]
    public function testAllSuspensionTypesHaveLabels(): void
    {
        $cases = SuspensionType::cases();

        $this->assertGreaterThan(0, $cases);

        foreach ($cases as $suspensionType) {
            $label = $suspensionType->label();

            $this->assertNotEmpty($label);
            $this->assertIsString($label);
        }
    }

    #[Test]
    public function testSpecifiedAdjournmentHasCorrectLabel(): void
    {
        $label = SuspensionType::SPECIFIED_ADJOURNMENT->label();

        $this->assertEquals('Gespecificeerde aanhouding', $label);
    }

    #[Test]
    public function testSuspensionHasCorrectLabel(): void
    {
        $label = SuspensionType::SUSPENSION->label();

        $this->assertEquals('Opschorting', $label);
    }

    #[Test]
    public function testSuspensionTypeValues(): void
    {
        $this->assertEquals('specified_adjournment', SuspensionType::SPECIFIED_ADJOURNMENT->value);
        $this->assertEquals('suspension', SuspensionType::SUSPENSION->value);
    }

    #[Test]
    public function testGetForPetitionTypeBezwaar(): void
    {
        $types = SuspensionType::getForPetitionType(PetitionVariant::BEZWAAR);

        $this->assertCount(2, $types);
        $this->assertContains(SuspensionType::SPECIFIED_ADJOURNMENT, $types);
        $this->assertContains(SuspensionType::SUSPENSION, $types);
    }

    #[Test]
    public function testGetForPetitionTypeWooVerzoek(): void
    {
        $types = SuspensionType::getForPetitionType(PetitionVariant::WOO_VERZOEK);

        $this->assertCount(2, $types);
        $this->assertContains(SuspensionType::SPECIFICATION, $types);
        $this->assertContains(SuspensionType::CONSULTATION, $types);
    }

    #[Test]
    public function testGetForPetitionTypeBeroep(): void
    {
        $types = SuspensionType::getForPetitionType(PetitionVariant::BEROEP);

        $this->assertEmpty($types);
    }
}
