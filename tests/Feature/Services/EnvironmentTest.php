<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Support\Environment;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Feature\FeatureTestCase;

class EnvironmentTest extends FeatureTestCase
{
    #[Test]
    #[DataProvider('booleanDataProvider')]
    public function testIsDevelopment(bool $value): void
    {
        App::expects('environment')
            ->with(['dev', 'development', 'local'])
            ->andReturn($value);

        $this->assertEquals($value, $this->getEnvironment()->isDevelopment());
    }

    #[Test]
    #[DataProvider('booleanDataProvider')]
    public function testIsProduction(bool $value): void
    {
        App::expects('environment')
            ->with(['production'])
            ->andReturn($value);

        $this->assertEquals($value, $this->getEnvironment()->isProduction());
    }

    #[Test]
    #[DataProvider('booleanDataProvider')]
    public function testIsTesting(bool $value): void
    {
        App::expects('environment')
            ->with(['test', 'testing'])
            ->andReturn($value);

        $this->assertEquals($value, $this->getEnvironment()->isTesting());
    }

    #[Test]
    #[DataProvider('isDevelopmentOrTestingDataProvider')]
    public function testIsDevelopmentOrTesting(bool $isDevelopment, bool $isTesting, bool $expected): void
    {
        App::expects('environment')
            ->with(['dev', 'development', 'local'])
            ->zeroOrMoreTimes()
            ->andReturn($isDevelopment);

        App::expects('environment')
            ->with(['test', 'testing'])
            ->zeroOrMoreTimes()
            ->andReturn($isTesting);

        $this->assertEquals($expected, $this->getEnvironment()->isDevelopmentOrTesting());
    }

    #[Test]
    public function testEnvironmentReturnsString(): void
    {
        App::expects('environment')->andReturn($this->faker->word());

        $this->expectException(RuntimeException::class);
        $this->getEnvironment()->isTesting();
    }

    public static function booleanDataProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    public static function isDevelopmentOrTestingDataProvider(): array
    {
        return [
            'its neither testing nor development' => [
                'isDevelopment' => false,
                'isTesting' => false,
                'expected' => false,
            ],
            'its testing' => [
                'isDevelopment' => false,
                'isTesting' => true,
                'expected' => true,
            ],
            'its development' => [
                'isDevelopment' => true,
                'isTesting' => false,
                'expected' => true,
            ],
            // this should normally never be the case
            'its both testing and development' => [
                'isDevelopment' => true,
                'isTesting' => true,
                'expected' => true,
            ],
        ];
    }

    private function getEnvironment(): Environment
    {
        return $this->app->get(Environment::class);
    }
}
