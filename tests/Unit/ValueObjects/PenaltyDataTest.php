<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\PenaltyData;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Throwable;
use TypeError;

class PenaltyDataTest extends TestCase
{
    #[Test]
    public function itCreatesValidPenalty(): void
    {
        $penalty = new PenaltyData(amount: 500, duration: 10);

        $this->assertSame(500, $penalty->amount);
        $this->assertSame(10, $penalty->duration);
    }

    #[Test]
    public function itRejectsZeroAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Penalty amount must be positive');

        new PenaltyData(amount: 0, duration: 10);
    }

    #[Test]
    public function itRejectsNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Penalty amount must be positive');

        new PenaltyData(amount: -100, duration: 10);
    }

    #[Test]
    public function itRejectsZeroDuration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Penalty duration must be positive');

        new PenaltyData(amount: 500, duration: 0);
    }

    #[Test]
    public function itRejectsNegativeDuration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Penalty duration must be positive');

        new PenaltyData(amount: 500, duration: -5);
    }

    #[Test]
    public function itConvertsFromArray(): void
    {
        $penalty = PenaltyData::fromArray(['amount' => 500, 'duration' => 10]);

        $this->assertSame(500, $penalty->amount);
        $this->assertSame(10, $penalty->duration);
    }

    #[Test]
    public function itConvertsToArray(): void
    {
        $penalty = new PenaltyData(amount: 500, duration: 10);

        $array = $penalty->toArray();

        $this->assertSame(['amount' => 500, 'duration' => 10], $array);
    }

    #[Test]
    public function itRoundTripsArrayConversion(): void
    {
        $original = ['amount' => 750, 'duration' => 15];

        $penalty = PenaltyData::fromArray($original);
        $result = $penalty->toArray();

        $this->assertSame($original, $result);
    }

    #[Test]
    public function itThrowsWhenMissingAmountInArray(): void
    {
        $this->expectException(Throwable::class);

        PenaltyData::fromArray(['duration' => 10]);
    }

    #[Test]
    public function itThrowsWhenMissingDurationInArray(): void
    {
        $this->expectException(Throwable::class);

        PenaltyData::fromArray(['amount' => 500]);
    }

    #[Test]
    public function itRejectsStringAmountInFromArray(): void
    {
        $this->expectException(TypeError::class);

        PenaltyData::fromArray(['amount' => '500', 'duration' => 10]);
    }

    #[Test]
    public function itRejectsStringDurationInFromArray(): void
    {
        $this->expectException(TypeError::class);

        PenaltyData::fromArray(['amount' => 500, 'duration' => '10']);
    }

    #[Test]
    public function itCreatesWithLargeValues(): void
    {
        $penalty = new PenaltyData(amount: 999_999, duration: 730);

        $this->assertSame(999_999, $penalty->amount);
        $this->assertSame(730, $penalty->duration);
    }

    #[Test]
    public function itCreatesWithMinimumValidValues(): void
    {
        $penalty = new PenaltyData(amount: 1, duration: 1);

        $this->assertSame(1, $penalty->amount);
        $this->assertSame(1, $penalty->duration);
    }

    #[Test]
    public function itIsImmutable(): void
    {
        $penalty = new PenaltyData(amount: 500, duration: 10);

        $this->assertSame(500, $penalty->amount);
        $this->assertSame(10, $penalty->duration);
    }
}
