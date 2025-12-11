<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Api;

use App\Services\Api\ApiTableConfigurationService;
use Illuminate\Http\Request;
use Tests\TestCase;

use function app;

class ApiTableConfigurationServiceTest extends TestCase
{
    private ApiTableConfigurationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ApiTableConfigurationService::class);
    }

    public function testIsTableAllowedReturnsTrueForConfiguredTables(): void
    {
        $this->assertTrue($this->service->isTableAllowed('users'));
        $this->assertTrue($this->service->isTableAllowed('petitions'));
    }

    public function testIsTableAllowedReturnsFalseForUnconfiguredTables(): void
    {
        $this->assertFalse($this->service->isTableAllowed('invalid-Table'));
        $this->assertFalse($this->service->isTableAllowed('secrets'));
    }

    public function testGetSelectableFieldsReturnsAllFieldsWhenNoneRequested(): void
    {
        $config = ['fields' => ['id', 'name', 'email']];
        $request = new Request();

        $fields = $this->service->getSelectableFields($config, $request);

        $this->assertEquals(['id', 'name', 'email'], $fields);
    }

    public function testGetPaginationOptionsEnforcesMaxPerPage(): void
    {
        $request = new Request(['per_page' => '999']);

        $paginationOptions = $this->service->getPaginationOptions($request);

        $this->assertEquals(['per_page' => 100], $paginationOptions);
    }

    public function testGetFiltersFromRequestReturnsUpdatedAtBeforeFilter(): void
    {
        $request = new Request(['updated_at_before' => '2024-01-15']);

        $filters = $this->service->getFilters($request);

        $this->assertEquals(['updated_at_before' => '2024-01-15'], $filters);
    }

    public function testGetFiltersFromRequestReturnsUpdatedAtAfterFilter(): void
    {
        $request = new Request(['updated_at_after' => '2024-01-15']);

        $filters = $this->service->getFilters($request);

        $this->assertEquals(['updated_at_after' => '2024-01-15'], $filters);
    }
}
