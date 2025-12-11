<?php

declare(strict_types=1);

namespace Tests\Smoke\User;

use App\Enums\Authorization\GlobalRole;
use App\Enums\RouteName;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\Smoke\SmokeTestCase;

use function __;

class UserCreateTest extends SmokeTestCase
{
    #[Test]
    public function testCreateUser(): void
    {
        $email = $this->faker->safeEmail();
        Config::set('auth.allowed_user_email_domains', $email);

        $user = User::factory()
            ->fullyVerified()
            ->withGlobalRole(GlobalRole::ADMINISTRATOR)
            ->create();

        $this->beUser($user)
            ->visitRoute(RouteName::ADMIN_USER_CREATE)
            ->type($this->faker->name(), 'name')
            ->type($email, 'email')
            ->press(__('user.create'))
            ->assertResponseStatus(200)
            ->see(__('general.saved'));
    }

    #[Test]
    public function testCreateUserWithExistingMailAddress(): void
    {
        $email = $this->faker->safeEmail();
        Config::set('auth.allowed_user_email_domains', $email);

        $user = User::factory()
            ->fullyVerified()
            ->withGlobalRole(GlobalRole::ADMINISTRATOR)
            ->create();

        User::factory()->create(['email' => $email]);

        $this->beUser($user)
            ->visitRoute(RouteName::ADMIN_USER_CREATE)
            ->type($this->faker->name(), 'name')
            ->type($email, 'email')
            ->press(__('user.create'))
            ->assertResponseStatus(200)
            ->see(__('validation.unique', ['attribute' => 'e-mailadres']));
    }

    #[Test]
    public function testCreateUserWithMailAddressSameAsCurrentUser(): void
    {
        $email = $this->faker->safeEmail();
        Config::set('auth.allowed_user_email_domains', $email);

        $user = User::factory()
            ->fullyVerified()
            ->withGlobalRole(GlobalRole::ADMINISTRATOR)
            ->create(['email' => $email]);

        $this->beUser($user)
            ->visitRoute(RouteName::ADMIN_USER_CREATE)
            ->type($this->faker->name(), 'name')
            ->type($email, 'email')
            ->press(__('user.create'))
            ->assertResponseStatus(200)
            ->see(__('validation.unique', ['attribute' => 'e-mailadres']));
    }
}
