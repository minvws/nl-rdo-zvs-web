<?php

declare(strict_types=1);

namespace App\Services\Api;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webmozart\Assert\Assert;

class ApiQueryBuilderService
{
    public function __construct(
        private readonly ApiTableConfigurationService $configService,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public function buildQuery(string $endpoint, array $config, Request $request): Builder
    {
        $table = $config['table'] ?? $endpoint;
        Assert::string($table);
        $query = DB::query()->from($table);

        $this->applyFieldSelection($query, $config);
        $this->applyFiltering($query, $config, $request);

        return $query;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function applyFieldSelection(Builder $query, array $config): void
    {
        $fields = $this->configService->getSelectableFields($config);
        $query->select($fields);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function applyFiltering(Builder $query, array $config, Request $request): void
    {
        $filters = $this->configService->getFilters($request);
        $filterableFields = $this->configService->getFilterableFields($config, $request);

        foreach ($filters as $field => $value) {
            if ($field === 'created_at_after') {
                $query->where('created_at', '>', $value);
                continue;
            }

            if ($field === 'created_at_before') {
                $query->where('created_at', '<', $value);
                continue;
            }

            if ($field === 'updated_at_after') {
                $query->where('updated_at', '>', $value);
                continue;
            }

            if ($field === 'updated_at_before') {
                $query->where('updated_at', '<', $value);
                continue;
            }
        }

        // Apply filterable field filters
        foreach ($filterableFields as $field => $value) {
            $query->where($field, $value);
        }
    }
}
