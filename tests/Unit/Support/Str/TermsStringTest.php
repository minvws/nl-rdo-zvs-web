<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Str;

use App\Enums\TermType;
use App\Support\Str\TermsString;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function __;
use function lcfirst;

class TermsStringTest extends TestCase
{
    #[Test]
    public function testFormatsASingleTerm(): void
    {
        $result = TermsString::format([TermType::DECISION_PERIOD]);

        $this->assertSame(lcfirst(__('term.decision_period.default')), $result);
    }

    #[Test]
    public function testFormatsMultipleTermsJoinedWithOf(): void
    {
        $result = TermsString::format([
            TermType::OBJECTION_PERIOD,
            TermType::DECISION_PERIOD,
        ]);

        $this->assertSame(
            lcfirst(__('term.objection_period.default'))
                . ' ' . __('general.or') . ' '
                . lcfirst(__('term.decision_period.default')),
            $result,
        );
    }

    #[Test]
    public function testFormatsAnEmptyListAsAnEmptyString(): void
    {
        $this->assertSame('', TermsString::format([]));
    }
}
