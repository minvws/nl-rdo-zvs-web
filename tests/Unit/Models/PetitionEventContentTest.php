<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\PetitionEventType;
use App\Models\PetitionEvent;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PetitionEventContentTest extends TestCase
{
    #[Test]
    public function testHasSameContentIgnoresIdentityAndTimestamps(): void
    {
        $first = $this->makeEvent(['id' => Str::uuid()->toString(), 'created_at' => CarbonImmutable::now()]);
        $second = $this->makeEvent([
            'id' => Str::uuid()->toString(),
            'created_at' => CarbonImmutable::now()->subDay(),
        ]);

        $this->assertTrue($first->hasSameContentAs($second));
    }

    #[Test]
    public function testHasSameContentTreatsEmptyPenaltiesAsNoPenalties(): void
    {
        $this->assertTrue($this->makeEvent(['penalties' => []])->hasSameContentAs($this->makeEvent(['penalties' => null])));
    }

    #[Test]
    public function testHasSameContentDetectsDifferences(): void
    {
        $event = $this->makeEvent();

        $this->assertFalse($event->hasSameContentAs($this->makeEvent(['type' => PetitionEventType::PETITION_RECEIVED])));
        $this->assertFalse($event->hasSameContentAs($this->makeEvent(['date' => CalendarDate::create('2025-01-14')])));
        $this->assertFalse($event->hasSameContentAs($this->makeEvent(['duration' => 43])));
        $this->assertFalse($event->hasSameContentAs($this->makeEvent(['reasoning' => 'other'])));
        $this->assertFalse($event->hasSameContentAs($this->makeEvent(['penalties' => [['amount' => 100, 'duration' => 5]]])));
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function makeEvent(array $overrides = []): PetitionEvent
    {
        return PetitionEvent::factory()->make([
            'petition_id' => '01a17c80-18c3-728e-8251-ced5b072f228',
            'type' => PetitionEventType::PRIMARY_DECISION,
            'date' => CalendarDate::create('2025-01-13'),
            'duration' => 42,
            'reasoning' => 'reason',
            'penalties' => [],
            ...$overrides,
        ]);
    }
}
