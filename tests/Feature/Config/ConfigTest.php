<?php

declare(strict_types=1);

namespace Tests\Feature\Config;

use App\Config\Config;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Feature\FeatureTestCase;

use function config;

class ConfigTest extends FeatureTestCase
{
    public function testIsAnArray(): void
    {
        $configKey = $this->faker->word();
        $configValue = [$this->faker->word() => $this->faker->boolean()];

        config()->set($configKey, $configValue);

        $this->assertSame($configValue, Config::array($configKey));
    }

    public function testIsNotAnArray(): void
    {
        $configKey = $this->faker->word();
        $configValue = $this->faker->boolean();

        config()->set($configKey, $configValue);

        $this->expectException(InvalidArgumentException::class);
        $this->assertSame($configValue, Config::array($configKey));
    }

    public function testIsAnArrayAllString(): void
    {
        $configKey = $this->faker->word();
        $configValue = [$this->faker->word(), $this->faker->word(), $this->faker->word()];

        config()->set($configKey, $configValue);

        $this->assertSame($configValue, Config::arrayAllString($configKey));
    }

    public function testIsNotAnArrayAllString(): void
    {
        $configKey = $this->faker->word();
        $configValue = $this->faker->boolean();

        config()->set($configKey, $configValue);

        $this->expectException(InvalidArgumentException::class);
        $this->assertSame($configValue, Config::arrayAllString($configKey));
    }

    public function testIsABoolean(): void
    {
        $configKey = $this->faker->word();
        $configValue = $this->faker->boolean();

        config()->set($configKey, $configValue);

        $this->assertSame($configValue, Config::boolean($configKey));
    }

    public function testIsNotABoolean(): void
    {
        $configKey = $this->faker->word();

        config()->set($configKey, []);

        $this->expectException(InvalidArgumentException::class);
        Config::boolean($configKey);
    }

    #[TestWith([15, 15], 'positive integer')]
    #[TestWith([0, 0], 'zero integer')]
    #[TestWith([-5, -5], 'negative integer')]
    #[TestWith(['123', 123], 'positive string')]
    #[TestWith(['0', 0], 'zero string')]
    #[TestWith(['-5', -5], 'negative string')]
    public function testIsAnInteger(int|string $configValue, int $expectedValue): void
    {
        $configKey = $this->faker->word();
        config()->set($configKey, $configValue);

        $this->assertSame($expectedValue, Config::integer($configKey));
    }

    public function testIsNotAnInteger(): void
    {
        $configKey = $this->faker->word();

        config()->set($configKey, []);

        $this->expectException(InvalidArgumentException::class);
        Config::integer($configKey);
    }

    public function testIsAString(): void
    {
        $configKey = $this->faker->word();
        $configValue = $this->faker->word();

        config()->set($configKey, $configValue);

        $this->assertSame($configValue, Config::string($configKey));
    }

    public function testIsNotAString(): void
    {
        $configKey = $this->faker->word();

        config()->set($configKey, []);

        $this->expectException(InvalidArgumentException::class);
        Config::string($configKey);
    }

    public function testIsAStringOrNull(): void
    {
        $configKey = $this->faker->word();
        $configValue = $this->faker->optional()->word();

        config()->set($configKey, $configValue);

        $this->assertSame($configValue, Config::stringOrNull($configKey));
    }

    public function testIsNotAStringOrNull(): void
    {
        $configKey = $this->faker->word();

        config()->set($configKey, []);

        $this->expectException(InvalidArgumentException::class);
        Config::stringOrNull($configKey);
    }
}
