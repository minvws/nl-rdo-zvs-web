<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Services\Api\ApiQueryBuilderService;
use App\Services\Api\ApiTableConfigurationService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use stdClass;

final readonly class GenericApiController
{
    public function __construct(
        private ApiTableConfigurationService $configService,
        private ApiQueryBuilderService $queryBuilder,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function index(string $table, Request $request): JsonResponse
    {
        if (!$this->configService->isTableAllowed($table)) {
            return $this->responseFactory->json(['error' => 'Table not found'], 404);
        }

        $config = $this->configService->getTableConfig($table);

        $query = $this->queryBuilder->buildQuery($table, $config, $request);
        $result = $this->paginateQuery($query, $request);

        return $this->responseFactory->json($this->formatCollectionResource($result, $config));
    }

    /**
     * @return LengthAwarePaginator<int, stdClass>
     */
    private function paginateQuery(Builder $query, Request $request): LengthAwarePaginator
    {
        $paginationOptions = $this->configService->getPaginationOptions($request);

        return $query->paginate($paginationOptions['per_page']);
    }

    /**
     * @param array<string, mixed> $config
     * @param LengthAwarePaginator<int, stdClass> $result
     *
     * @return array<string, mixed>
     */
    private function formatCollectionResource(LengthAwarePaginator $result, array $config): array
    {
        return [
            'data' => $result->items(),
            'pagination' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage(),
                'from' => $result->firstItem(),
                'to' => $result->lastItem(),
            ],
            'meta' => [
                'available_fields' => $config['fields'] ?? [],
            ],
        ];
    }
}
