<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Api;

use App\Services\Api\ApiQueryBuilderService;
use App\Services\Api\ApiTableConfigurationService;
use Illuminate\Http\Request;
use Tests\TestCase;

use function app;

class ApiQueryBuilderServiceTest extends TestCase
{
    private ApiQueryBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $configService = app(ApiTableConfigurationService::class);
        $this->service = new ApiQueryBuilderService($configService);
    }

    public function testBuildQueryAppliesCreatedAtBeforeFilter(): void
    {
        $config = [
            'table' => 'users',
            'fields' => ['id', 'name'],
        ];
        $request = new Request(['created_at_before' => '2024-01-15']);

        $query = $this->service->buildQuery('users', $config, $request);

        $sql = $query->toSql();
        $this->assertStringContainsString('"created_at" < ?', $sql);
    }

    public function testBuildQueryAppliesUpdatedAtAfterFilter(): void
    {
        $config = [
            'table' => 'users',
            'fields' => ['id', 'name'],
        ];
        $request = new Request(['updated_at_after' => '2024-01-15']);

        $query = $this->service->buildQuery('users', $config, $request);

        $sql = $query->toSql();
        $this->assertStringContainsString('"updated_at" > ?', $sql);
    }

    public function testBuildQueryAppliesUpdatedAtBeforeFilter(): void
    {
        $config = [
            'table' => 'users',
            'fields' => ['id', 'name'],
        ];
        $request = new Request(['updated_at_before' => '2024-01-15']);

        $query = $this->service->buildQuery('users', $config, $request);

        $sql = $query->toSql();
        $this->assertStringContainsString('"updated_at" < ?', $sql);
    }

    public function testBuildQueryAppliesGenericFilter(): void
    {
        $config = [
            'table' => 'users',
            'fields' => ['id', 'name'],
            'filterable_fields' => ['name'],
        ];
        $request = new Request(['name' => 'John']);

        $query = $this->service->buildQuery('users', $config, $request);

        $sql = $query->toSql();
        $this->assertStringContainsString('"name" = ?', $sql);
    }
}
