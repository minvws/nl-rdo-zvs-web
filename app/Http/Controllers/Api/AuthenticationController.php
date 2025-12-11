<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\ApiAuthenticationRequest;
use App\Models\ApiUser;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use MinVWS\AuditLogger\Events\Logging\UserLoginLogEvent;
use MinVWS\Logging\Laravel\LogService;

final readonly class AuthenticationController
{
    public function __construct(
        private Hasher $hasher,
        private LogService $logService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function login(ApiAuthenticationRequest $request): JsonResponse
    {
        $apiKey = $request->string('api_key')->toString();
        $apiSecret = $request->string('api_secret')->toString();

        $apiUser = ApiUser::query()
            ->where('api_key', $apiKey)->firstOrFail();

        if (!$this->hasher->check($apiSecret, $apiUser->api_secret)) {
            $this->logService->log((new UserLoginLogEvent())
                ->asExecute()
                ->withData(['api_key' => $apiKey, 'api_user_name' => $apiUser->name]));

            return $this->responseFactory->json([
                'error' => 'Invalid API credentials.',
            ], 401);
        }

        $this->logService->log((new UserLoginLogEvent())
            ->asExecute()
            ->withData(['api_key' => $apiKey, 'api_user_name' => $apiUser->name, 'success' => true]));

        $token = $apiUser->createToken('api-token')->plainTextToken;

        return $this->responseFactory->json([
            'access_token' => $token,
        ]);
    }
}
