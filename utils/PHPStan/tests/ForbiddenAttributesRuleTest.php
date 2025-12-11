<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Utils\PHPStan\Rules\ForbiddenAttributesRule;

#[CoversClass(ForbiddenAttributesRule::class)]
class ForbiddenAttributesRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbiddenAttributesRule(['Attribute1', 'Attribute2']);
    }

    public function testForbiddenAttributes(): void
    {
        $this->analyse([__DIR__ . '/testfiles/ForbiddenAttributes.php'], [
            [
                'Usage of the attribute "Attribute1" is not allowed.',
                5,
            ],
            [
                'Usage of the attribute "Attribute2" is not allowed.',
                9,
            ],
            [
                'Usage of the attribute "Attribute2" is not allowed.',
                15,
            ],
        ]);
    }
}
