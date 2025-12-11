<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Events\UserCreatedEvent;
use App\Listeners\LogUserCreated;
use App\Models\User;
use MinVWS\Logging\Laravel\LogService;
use Tests\Feature\FeatureTestCase;

class LogUserCreatedTest extends FeatureTestCase
{
    public function testLogUserCreated(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $event = new UserCreatedEvent($user);

        $listener = new LogUserCreated($logService);
        $listener($event);
    }

    public function testLogUserCreatedWithActor(): void
    {
        $this->actingAs(User::factory()->make());

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $event = new UserCreatedEvent($user);

        $listener = new LogUserCreated($logService);
        $listener($event);
    }
}
