<?php

declare(strict_types=1);

namespace App\Services\Api;

use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

use function array_filter;
use function array_key_exists;
use function is_string;

class ApiTableConfigurationService
{
    /**
     * @param array<string, array<string, mixed>> $tables
     */
    public function __construct(
        #[Config('api_tables.tables', [])]
        private readonly array $tables,
    ) {
    }

    public function isTableAllowed(string $table): bool
    {
        return array_key_exists($table, $this->tables);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTableConfig(string $table): array
    {
        return $this->tables[$table];
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string>
     */
    public function getSelectableFields(array $config): array
    {
        $fields = Arr::array($config, 'fields');

        return array_filter($fields, is_string(...));
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilters(Request $request): array
    {
        $filters = [];

        if ($request->has('created_at_after')) {
            $filters['created_at_after'] = $request->input('created_at_after');
        }

        if ($request->has('created_at_before')) {
            $filters['created_at_before'] = $request->input('created_at_before');
        }

        if ($request->has('updated_at_before')) {
            $filters['updated_at_before'] = $request->input('updated_at_before');
        }

        if ($request->has('updated_at_after')) {
            $filters['updated_at_after'] = $request->input('updated_at_after');
        }

        return $filters;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    public function getFilterableFields(array $config, Request $request): array
    {
        $filters = [];
        $filterableFields = Arr::array($config, 'fields', []);

        foreach ($filterableFields as $field) {
            if (is_string($field) && $request->has($field)) {
                $filters[$field] = $request->input($field);
            }
        }

        return $filters;
    }

    /**
     * @return array<string, int>
     */
    public function getPaginationOptions(Request $request): array
    {
        $perPage = $request->integer(
            'per_page',
            \Illuminate\Support\Facades\Config::integer('api_tables.pagination.default_per_page', 15),
        );
        $maxPerPage = \Illuminate\Support\Facades\Config::integer('api_tables.pagination.max_per_page', 100);

        if ($perPage > $maxPerPage) {
            $perPage = $maxPerPage;
        }

        return [
            'per_page' => $perPage,
        ];
    }
}
