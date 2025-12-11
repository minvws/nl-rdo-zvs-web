<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\ApiUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTestCase;

class AuthenticationControllerTest extends FeatureTestCase
{
    public function testLogin(): void
    {
        $apiKey = Str::random(64);
        $apiSecret = Str::random(128);

        ApiUser::factory()->create([
            'api_key' => $apiKey,
            'api_secret' => Hash::make($apiSecret),
        ]);

        $response = $this->postJson('/api/login', [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'access_token',
        ]);
    }

    public function testLoginFails(): void
    {
        $apiKey = Str::random(64);
        $apiSecret = Str::random(128);
        $wrongSecret = Str::random(128);

        ApiUser::factory()->create([
            'api_key' => $apiKey,
            'api_secret' => Hash::make($apiSecret),
        ]);

        $response = $this->postJson('/api/login', [
            'api_key' => $apiKey,
            'api_secret' => $wrongSecret,
        ]);

        $response->assertStatus(401);
    }
}
