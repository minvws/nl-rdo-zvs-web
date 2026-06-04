<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\AdjournmentEndReason;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AdjournmentEndReasonTest extends TestCase
{
    #[Test]
    public function testEventLabel(): void
    {
        $this->assertSame('Gebeurtenis heeft plaatsgevonden', AdjournmentEndReason::Event->label());
    }

    #[Test]
    public function testWithdrawalLabel(): void
    {
        $this->assertSame('Intrekking van akkoord', AdjournmentEndReason::Withdrawal->label());
    }
}
