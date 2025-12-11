<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Database\Casts;

use App\Models\User;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class DatetimeWithTimezoneCastTest extends FeatureTestCase
{
    #[Test]
    public function testGet(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => $this->faker->dateTime(),
        ]);

        $this->assertInstanceOf(CarbonImmutable::class, $user->email_verified_at);
    }

    #[Test]
    public function testGetWithToArray(): void
    {
        $emailVerifiedAt = $this->faker->dateTime();

        $user = User::factory()->create([
            'email_verified_at' => $emailVerifiedAt,
        ]);
        $userData = $user->toArray();

        $this->assertEquals($emailVerifiedAt->jsonSerialize(), $userData['email_verified_at']);
    }
}
