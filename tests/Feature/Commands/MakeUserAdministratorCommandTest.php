<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Enums\Authorization\GlobalRole;
use App\Models\User;
use App\Models\UserGlobalRole;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class MakeUserAdministratorCommandTest extends FeatureTestCase
{
    #[Test]
    public function testSuccessfulCommand(): void
    {
        $user = User::factory()->create();

        $this->artisan('app:make-user-administrator')
            ->expectsQuestion('Give the user\'s email address', $user->email)
            ->assertSuccessful();

        $this->assertDatabaseHas(UserGlobalRole::class, [
            'user_id' => $user->id,
            'role' => GlobalRole::ADMINISTRATOR->value,
        ]);

        $this->assertDatabaseHas(User::class, [
            'id' => $user->id,
        ]);
    }

    #[Test]
    public function testUnsuccessfulCommand(): void
    {
        $this->artisan('app:make-user-administrator')
            ->expectsQuestion('Give the user\'s email address', $this->faker->email)
            ->assertFailed();
    }
}
