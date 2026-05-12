<?php

declare(strict_types=1);

namespace Tests\Feature\Commands\Api;

use App\Models\ApiUser;
use Tests\Feature\FeatureTestCase;

use function sprintf;

class GenerateApiCredentialsTest extends FeatureTestCase
{
    public function testCommand(): void
    {
        $apiUser = ApiUser::factory()->create();

        $result = $this->artisan('api:generate-credentials', ['api_user_id' => $apiUser->id]);
        $result->expectsConfirmation('This command overwrites the current credentials of the API user. Do you wish to continue?', 'yes');
        $result->assertSuccessful();
        $result->expectsOutputToContain(sprintf('API Credentials generated for API User ID: %s', $apiUser->id));
    }

    public function testCommandCancelled(): void
    {
        $apiUser = ApiUser::factory()->create();

        $result = $this->artisan('api:generate-credentials', ['api_user_id' => $apiUser->id]);
        $result->expectsConfirmation('This command overwrites the current credentials of the API user. Do you wish to continue?', 'no');
        $result->assertSuccessful();
        $result->expectsOutputToContain('Operation cancelled.');
    }

    public function testCommandNotFound(): void
    {
        $apiUserId = $this->faker->numberBetween(1, 1000);
        $result = $this->artisan('api:generate-credentials', ['api_user_id' => $apiUserId]);
        $result->expectsConfirmation('This command overwrites the current credentials of the API user. Do you wish to continue?', 'yes');
        $result->assertFailed();
    }
}
