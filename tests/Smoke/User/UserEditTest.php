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

class UserEditTest extends SmokeTestCase
{
    #[Test]
    public function testEditUser(): void
    {
        $email = $this->faker->safeEmail();
        Config::set('auth.allowed_user_email_domains', $email);

        $admin = User::factory()
            ->fullyVerified()
            ->withGlobalRole(GlobalRole::ADMINISTRATOR)
            ->create();

        $user = User::factory()->create();

        $this->beUser($admin)
            ->visitRoute(RouteName::ADMIN_USER_EDIT, ['user' => $user])
            ->type($this->faker->name(), 'name')
            ->type($email, 'email')
            ->press(__('general.save'))
            ->assertResponseStatus(200)
            ->see(__('general.saved'));
    }

    #[Test]
    public function testEditUserWithoutChangingEmail(): void
    {
        $email = $this->faker->safeEmail();
        Config::set('auth.allowed_user_email_domains', $email);

        $admin = User::factory()
            ->fullyVerified()
            ->withGlobalRole(GlobalRole::ADMINISTRATOR)
            ->create();

        $user = User::factory()->create(
            ['email' => $email],
        );

        $this->beUser($admin)
            ->visitRoute(RouteName::ADMIN_USER_EDIT, ['user' => $user])
            ->type($this->faker->name(), 'name')
            ->press(__('general.save'))
            ->assertResponseStatus(200)
            ->see(__('general.saved'));
    }

    #[Test]
    public function testEditCurrentUser(): void
    {
        $email = $this->faker->safeEmail();
        Config::set('auth.allowed_user_email_domains', $email);

        $admin = User::factory()
            ->fullyVerified()
            ->withGlobalRole(GlobalRole::ADMINISTRATOR)
            ->create();

        $this->beUser($admin)
            ->visitRoute(RouteName::ADMIN_USER_EDIT, ['user' => $admin])
            ->type($this->faker->name(), 'name')
            ->type($email, 'email')
            ->press(__('general.save'))
            ->assertResponseStatus(200)
            ->see(__('general.saved'));
    }

    #[Test]
    public function testEditCurrentUserWithoutChangingEmail(): void
    {
        $email = $this->faker->safeEmail();
        Config::set('auth.allowed_user_email_domains', $email);

        $admin = User::factory()
            ->fullyVerified()
            ->withGlobalRole(GlobalRole::ADMINISTRATOR)
            ->create([
                'email' => $email,
            ]);

        $this->beUser($admin)
            ->visitRoute(RouteName::ADMIN_USER_EDIT, ['user' => $admin])
            ->type($this->faker->name(), 'name')
            ->press(__('general.save'))
            ->assertResponseStatus(200)
            ->see(__('general.saved'));
    }

    #[Test]
    public function testEditUserChangeEmailToExistingMailAddress(): void
    {
        $email = $this->faker->safeEmail();
        Config::set('auth.allowed_user_email_domains', $email);

        $admin = User::factory()
            ->fullyVerified()
            ->withGlobalRole(GlobalRole::ADMINISTRATOR)
            ->create();

        $user = User::factory()->create();
        User::factory()->create([
            'email' => $email,
        ]);

        $this->beUser($admin)
            ->visitRoute(RouteName::ADMIN_USER_EDIT, ['user' => $user])
            ->type($this->faker->name(), 'name')
            ->type($email, 'email')
            ->press(__('general.save'))
            ->assertResponseStatus(200)
            ->see(__('validation.unique', ['attribute' => 'e-mailadres']));
    }
}
