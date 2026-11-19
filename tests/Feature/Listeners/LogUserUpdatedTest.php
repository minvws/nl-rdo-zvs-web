<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Events\UserUpdatedEvent;
use App\Listeners\LogUserUpdated;
use App\Models\User;
use MinVWS\Logging\Laravel\LogService;
use Mockery;
use Tests\Feature\FeatureTestCase;

class LogUserUpdatedTest extends FeatureTestCase
{
    public function testLogUserUpdated(): void
    {
        $logService = Mockery::mock(LogService::class);
        $logService->expects('log');

        $user = User::factory()->make();
        $event = new UserUpdatedEvent($user);

        $listener = new LogUserUpdated($logService);
        $listener($event);
    }

    public function testLogUserUpdatedWithUser(): void
    {
        $this->actingAs(User::factory()->make());
        $logService = Mockery::mock(LogService::class);
        $logService->expects('log');

        $user = User::factory()->make();
        $event = new UserUpdatedEvent($user);

        $listener = new LogUserUpdated($logService);
        $listener($event);
    }
}
