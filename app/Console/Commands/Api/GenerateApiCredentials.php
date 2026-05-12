<?php

declare(strict_types=1);

namespace App\Console\Commands\Api;

use App\Models\ApiUser;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Str;
use MinVWS\AuditLogger\Events\Logging\ResetCredentialsLogEvent;
use MinVWS\Logging\Laravel\LogService;
use Throwable;

use function sprintf;

#[Signature('api:generate-credentials {api_user_id}')]
#[Description('Generates API credentials for a given (API) user ID.')]
class GenerateApiCredentials extends Command
{
    public function handle(Hasher $hasher, LogService $logService): int
    {
        $apiUserId = $this->argument('api_user_id');

        if (!$this->confirm('This command overwrites the current credentials of the API user. Do you wish to continue?')) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        try {
            $apiUser = ApiUser::query()->findSole($apiUserId);

            $apiKey = Str::random(64);
            $apiSecret = Str::random(128);

            $apiUser->api_key = $apiKey;
            $apiUser->api_secret = $hasher->make($apiSecret);
            $apiUser->save();

            $logService->log((new ResetCredentialsLogEvent())
                ->asUpdate()
                ->withData(['name' => $apiUser->name, 'api_key' => $apiKey, 'command' => 'generate-api-credentials']));

            $this->info(sprintf("API Credentials generated for API User ID: %s", $apiUserId));
            $this->line(sprintf("API Key: %s", $apiKey));
            $this->line(sprintf("API Secret: %s (Remember to store this securely!)", $apiSecret));

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Error: " . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
