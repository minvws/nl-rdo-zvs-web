<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Services\DatabaseHealthService;
use App\Services\Virusscanner\VirusscannerInterface;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class HealthControllerTest extends FeatureTestCase
{
    public function testAlwaysReturnValidResponse(): void
    {
        $this->get('/health')->assertOk();
    }

    #[DataProvider('data')]
    public function testItReturnsTheCorrectResult(bool $databaseHealth, bool $virusscannerHealth, bool $isHealthy): void
    {
        $this->mock(DatabaseHealthService::class, static function (MockInterface $mock) use ($databaseHealth): void {
            $mock->expects('isHealthy')->andReturn($databaseHealth);
        });

        $this->mock(VirusscannerInterface::class, static function (MockInterface $mock) use ($virusscannerHealth): void {
            $mock->expects('isHealthy')->andReturn($virusscannerHealth);
        });

        $this->get('/health')->assertExactJson([
            'healthy' => $isHealthy,
            'externals' => [
                'database' => $databaseHealth,
                'virusscanner' => $virusscannerHealth,
            ],
        ]);
    }

    /**
     * @return array<array<mixed>>
     */
    public static function data(): array
    {
        return [
            [true, true, true],
            [false, true, false],
            [true, false, false],
            [false, false, false],
        ];
    }
}
