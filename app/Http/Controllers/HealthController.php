<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DatabaseHealthService;
use App\Services\Virusscanner\VirusscannerInterface;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

use const JSON_PRETTY_PRINT;

final readonly class HealthController
{
    public function __construct(
        private DatabaseHealthService $databaseHealthService,
        private VirusscannerInterface $virusscanner,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $databaseHealth = $this->databaseHealthService->isHealthy();
        $virusscannerHealth = $this->virusscanner->isHealthy();

        $isHealthy = $databaseHealth && $virusscannerHealth;

        return $this->responseFactory->json(
            [
                'healthy' => $isHealthy,
                'externals' => [
                    'database' => $databaseHealth,
                    'virusscanner' => $virusscannerHealth,
                ],
            ],
            $isHealthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE,
            [],
            JSON_PRETTY_PRINT,
        );
    }
}
