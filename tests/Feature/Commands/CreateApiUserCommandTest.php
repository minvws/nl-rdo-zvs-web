<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Models\ApiUser;
use Exception;
use Illuminate\Contracts\Hashing\Hasher;
use Tests\Feature\FeatureTestCase;

use function sprintf;
use function strlen;

class CreateApiUserCommandTest extends FeatureTestCase
{
    public function testCommand(): void
    {
        $name = $this->faker->name();

        $this->artisan('api:create-user')
            ->expectsQuestion('API User Name', $name)
            ->assertSuccessful();

        $this->assertDatabaseHas(ApiUser::class, [
            'name' => $name,
        ]);

        $apiUser = ApiUser::where('name', $name)->first();
        $this->assertNotNull($apiUser);
        $this->assertNotEmpty($apiUser->api_key);
        $this->assertNotEmpty($apiUser->api_secret);
        $this->assertEquals(64, strlen($apiUser->api_key));
    }

    public function testCommandWithDefaultName(): void
    {
        $this->artisan('api:create-user')
            ->expectsQuestion('API User Name', 'api-user')
            ->assertSuccessful();

        $this->assertDatabaseHas(ApiUser::class, [
            'name' => 'api-user',
        ]);
    }

    public function testCommandWhenCreationFails(): void
    {
        $hasher = $this->mock(Hasher::class);
        $hasher->expects('make')
            ->andThrow(new Exception('Hashing failed'));

        $name = $this->faker->name();

        $this->artisan('api:create-user')
            ->expectsQuestion('API User Name', $name)
            ->assertFailed();

        $this->assertDatabaseMissing(ApiUser::class, [
            'name' => $name,
        ]);
    }

    public function testCommandGeneratesUniqueCredentials(): void
    {
        $firstName = $this->faker->unique()->firstName();
        $secondName = $this->faker->unique()->firstName();

        $this->artisan('api:create-user')
            ->expectsQuestion('API User Name', $firstName)
            ->assertSuccessful();

        $this->artisan('api:create-user')
            ->expectsQuestion('API User Name', $secondName)
            ->assertSuccessful();

        $firstUser = ApiUser::where('name', $firstName)->first();
        $secondUser = ApiUser::where('name', $secondName)->first();

        $this->assertNotEquals($firstUser->api_key, $secondUser->api_key);
        $this->assertNotEquals($firstUser->api_secret, $secondUser->api_secret);
    }

    public function testCommandDisplaysGeneratedCredentials(): void
    {
        $name = $this->faker->name();

        $this->artisan('api:create-user')
            ->expectsQuestion('API User Name', $name)
            ->expectsOutputToContain('API User created successfully')
            ->expectsOutputToContain(sprintf("Name: %s", $name))
            ->expectsOutputToContain('API Key:')
            ->expectsOutputToContain('API Secret:')
            ->expectsOutputToContain('Remember to store these credentials securely!')
            ->assertSuccessful();
    }
}
