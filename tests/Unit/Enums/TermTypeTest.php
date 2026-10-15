<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\TermType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TermTypeTest extends TestCase
{
    #[Test]
    public function testDecisionPeriodLabel(): void
    {
        $this->assertSame('Beslistermijn', TermType::DECISION_PERIOD->label());
    }

    #[Test]
    #[DataProvider('termTypeProvider')]
    public function testEveryCaseHasATranslatedLabel(TermType $termType): void
    {
        $label = $termType->label();

        $this->assertNotSame('', $label);
        $this->assertStringStartsNotWith('term.', $label);
    }

    /**
     * @return array<string, array{TermType}>
     */
    public static function termTypeProvider(): array
    {
        $cases = [];
        foreach (TermType::cases() as $case) {
            $cases[$case->name] = [$case];
        }

        return $cases;
    }
}
