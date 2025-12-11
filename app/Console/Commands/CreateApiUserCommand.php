<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ApiUser;
use Illuminate\Console\Command;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Str;
use Laravel\Prompts\TextPrompt;
use MinVWS\AuditLogger\Events\Logging\UserCreatedLogEvent;
use MinVWS\Logging\Laravel\LogService;
use Throwable;
use Webmozart\Assert\Assert;

use function sprintf;

class CreateApiUserCommand extends Command
{
    /** @var string $signature */
    protected $signature = 'api:create-user';

    /** @var string $description */
    protected $description = 'Create a new API user with generated credentials';

    public function handle(Hasher $hasher, LogService $logService): int
    {
        $name = $this->askName();

        $apiKey = Str::random(64);
        $apiSecret = Str::random(128);

        try {
            $apiUser = ApiUser::query()->create([
                'name' => $name,
                'api_key' => $apiKey,
                'api_secret' => $hasher->make($apiSecret),
            ]);

            $logService->log((new UserCreatedLogEvent())
                ->asCreate()
                ->withData(['name' => $apiUser->name, 'api_key' => $apiKey, 'command' => 'create-api-user']));

            $this->info(sprintf("API User created successfully (ID: %s)", $apiUser->id));
            $this->line(sprintf("Name: %s", $apiUser->name));
            $this->line(sprintf("API Key: %s", $apiKey));
            $this->line(sprintf("API Secret: %s", $apiSecret));
            $this->warn("Remember to store these credentials securely!");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Error creating API user: " . $e->getMessage());

            return self::FAILURE;
        }
    }

    private function askName(): string
    {
        $textPrompt = new TextPrompt(
            label: 'API User Name',
            default: 'api-user',
            validate: [
                'string',
                'required',
                'max:255',
            ],
        );

        $prompt = $textPrompt->prompt();
        Assert::string($prompt);

        return $prompt;
    }
}
