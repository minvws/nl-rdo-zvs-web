<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Casts;

use App\Models\Casts\UuidCast;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Tests\TestCase;

class UuidCastTest extends TestCase
{
    #[Test]
    public function testWithUuidStringGivesUuid(): void
    {
        $uuidCast = new UuidCast();

        $result = $uuidCast->get(new class extends Model {
            protected $casts = [
                'uuid' => UuidCast::class,
            ];
        }, 'uuid', Uuid::uuid7()->toString(), []);

        $this->assertInstanceOf(UuidInterface::class, $result);
    }

    #[Test]
    public function getWithUuidGivesUuid(): void
    {
        $uuidCast = new UuidCast();

        $result = $uuidCast->get(new class extends Model {
            protected $casts = [
                'uuid' => UuidCast::class,
            ];
        }, 'uuid', Uuid::uuid7(), []);

        $this->assertInstanceOf(UuidInterface::class, $result);
    }
}
