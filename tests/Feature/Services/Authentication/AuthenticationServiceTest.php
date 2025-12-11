<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Authentication;

use App\Models\User;
use App\Services\Authentication\AuthenticationException;
use App\Services\Authentication\AuthenticationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\Feature\FeatureTestCase;

class AuthenticationServiceTest extends FeatureTestCase
{
    public function testUser(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user);

        $authenticationService = $this->app->get(AuthenticationService::class);
        $this->assertTrue($user->id->equals($authenticationService->user()->id));
    }

    public function testUserFailsIfNoUser(): void
    {
        $authenticationService = $this->app->get(AuthenticationService::class);

        $this->expectException(AuthenticationException::class);
        $authenticationService->user();
    }

    public function testUserFailsIfDeletedUser(): void
    {
        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user);

        User::query()
            ->where('id', $user->id)
            ->delete();

        $authenticationService = $this->app->get(AuthenticationService::class);

        $this->expectException(ModelNotFoundException::class);
        $this->assertEquals($user->id, $authenticationService->user()->id);
    }
}
