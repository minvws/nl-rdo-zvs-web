<?php

declare(strict_types=1);

namespace Tests\Feature\Commands\App;

use App\Actions\User\UserCreateAction;
use App\Models\User;
use Exception;
use Tests\Feature\FeatureTestCase;

class CreateAdminUserCommandTest extends FeatureTestCase
{
    public function testCommand(): void
    {
        $name = $this->faker->name();
        $email = $this->faker->safeEmail();

        $this->artisan('app:create-admin-user')
            ->expectsQuestion('Name', $name)
            ->expectsQuestion('Email', $email)
            ->assertSuccessful();

        $this->assertDatabaseHas(User::class, [
            'name' => $name,
            'email' => $email,
            'password' => null,
        ]);
    }

    public function testCommandWhenCreateFailed(): void
    {
        $this->artisan('app:create-admin-user')
            ->expectsQuestion('Name', $this->faker->name())
            ->expectsQuestion('Email', $this->faker->word())
            ->assertFailed();
    }

    public function testCommandWhenCreateFailedByAction(): void
    {
        $action = $this->mock(UserCreateAction::class);
        $action
            ->expects('execute')
            ->andThrow(new Exception('Failed to create user'));

        $name = $this->faker->name();
        $email = $this->faker->safeEmail();

        $this->artisan('app:create-admin-user')
            ->expectsQuestion('Name', $name)
            ->expectsQuestion('Email', $email)
            ->assertFailed();
    }
}
