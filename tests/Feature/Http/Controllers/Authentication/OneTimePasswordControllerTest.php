<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Authentication;

use App\Enums\Authorization\GlobalRole;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\User;
use App\Services\Authentication\OneTimePassword\OneTimePasswordInterface;
use Illuminate\Support\Facades\Crypt;
use Mockery\MockInterface;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

use function sprintf;

class OneTimePasswordControllerTest extends FeatureTestCase
{
    public function testOneTimePasswordScreenCanBeRendered(): void
    {
        Department::factory()->create();
        $user = User::factory()->fullyVerified()->create();

        $this->beUser($user)
            ->getByRoute('one-time-password.authenticate')
            ->assertStatus(200);
    }

    public function testOtpCodeCanBeDisabled(): void
    {
        $user = User::factory()->fullyVerified()->create();

        $this->beUser($user)
            ->postByRoute('profile.otp.disable')
            ->assertRedirectToRoute(RouteName::LOGIN);

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $user = $this->getUserById($user->id);
        $this->assertNull($user->otp_verified_at);
    }

    public function testOtpCodeCanBeEnabled(): void
    {
        $user = User::factory()->fullyVerified()->otpDisabled()->create();

        $this->beUser($user)
            ->postByRoute('one-time-password.enable')
            ->assertRedirectToRoute('one-time-password.enroll');

        $result = $this->getUserById($user->id);

        $this->assertNull($result->otp_verified_at);
        $this->assertNotNull($result->otp_secret);
    }

    public function testOtpCodeCanBeConfirmed(): void
    {
        $code = sprintf('%06d', $this->faker->numberBetween(0, 999_999));

        $otpSecret = $this->faker->word();

        $user = User::factory()->fullyVerified()->state([
            'otp_verified_at' => null,
            'otp_secret' => Crypt::encrypt($otpSecret),
        ])->create();

        /** @var OneTimePasswordInterface&MockInterface $oneTimePassword */
        $oneTimePassword = $this->mock(OneTimePasswordInterface::class);
        $oneTimePassword->expects('isCodeValid')
            ->once()
            ->with($code, $otpSecret)
            ->andReturnTrue();

        $this->beUser($user)
            ->fromRoute('profile.edit')
            ->postByRoute('one-time-password.confirm', data: ['otp_confirmation' => $code])
            ->assertRedirectToRoute('profile.edit');

        $result = $this->getUserById($user->id);
        $this->assertNotNull($result->otp_verified_at);
    }

    public function testOtpCodeIfInvalid(): void
    {
        $code = sprintf('%06d', $this->faker->numberBetween(0, 999_999));
        $otpSecret = $this->faker->word();

        $user = User::factory()->fullyVerified()->state([
            'otp_verified_at' => null,
            'otp_secret' => Crypt::encrypt($otpSecret),
        ])->create();

        /** @var OneTimePasswordInterface&MockInterface $oneTimePassword */
        $oneTimePassword = $this->mock(OneTimePasswordInterface::class);
        $oneTimePassword->expects('isCodeValid')
            ->once()
            ->with($code, $otpSecret)
            ->andReturnFalse();

        $this->beUser($user)
            ->fromRoute('profile.edit')
            ->postByRoute('one-time-password.confirm', data: ['otp_confirmation' => $code])
            ->assertSessionHasErrors()
            ->assertRedirectToRoute('profile.edit');

        $result = $this->getUserById($user->id);
        $this->assertNull($result->otp_verified_at);
    }

    public function testOneTimePasswordCanBeValidated(): void
    {
        ConfigHelper::set('auth.one_time_password.driver', 'fake');
        $code = sprintf('%06d', $this->faker->numberBetween(0, 999_999));

        $user = User::factory()->fullyVerified()->create();
        $department = Department::factory()->create();

        // make sure session is not yet valid (redirects to otp-confirm)
        $this->beUser($user, false, $department)
            ->getByRoute('dashboard')
            ->assertRedirectToRoute('one-time-password.authenticate', ['next' => '/']);

        // post otp code
        $this->beUser($user, false)
            ->fromRoute('one-time-password.authenticate')
            ->postByRoute('one-time-password.authenticate', data: ['code' => $code])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department]);

        // make sure session is now valid (does not redirect)
        $this->beUser($user, false)
            ->getByRoute('dashboard')
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department]);
    }

    public function testOneTimePasswordFailsIfIncorrect(): void
    {
        $code = sprintf('%06d', $this->faker->numberBetween(0, 999_999));
        ConfigHelper::set('auth.one_time_password.driver', 'timed');

        $user = User::factory()->fullyVerified()->create();

        // make sure session is not yet valid (redirects to otp-confirm)
        $this->beUser($user, false)
            ->getByRoute('dashboard')
            ->assertRedirectToRoute('one-time-password.authenticate', ['next' => '/']);

        // post invalid otp code
        $this->beUser($user, false)
            ->fromRoute('one-time-password.authenticate')
            ->postByRoute('one-time-password.authenticate', data: ['code' => $code])
            ->assertRedirectToRoute('one-time-password.authenticate');

        // make sure session is still not valid (redirects to otp-confirm)
        $this->beUser($user, false)
            ->getByRoute('dashboard')
            ->assertRedirectToRoute('one-time-password.authenticate', ['next' => '/']);
    }

    public function testOneTimePasswordCanNotBeBypassed(): void
    {
        ConfigHelper::set('auth.one_time_password.driver', 'timed');

        $user = User::factory()->fullyVerified()->create();

        // make sure session is not yet valid (redirects to otp-confirm)
        $this->beUser($user, false)
            ->getByRoute('dashboard')
            ->assertRedirectToRoute('one-time-password.authenticate', ['next' => '/']);

        // try to go to profile page (to disable 2FA)
        $this->beUser($user, false)
            ->getByRoute('profile.edit')
            ->assertRedirectToRoute('one-time-password.authenticate', ['next' => '/profile']);
    }

    public function testAdminWithoutDepartmentRolesRedirectsToAdminIndex(): void
    {
        $code = sprintf('%06d', $this->faker->numberBetween(0, 999_999));
        ConfigHelper::set('auth.one_time_password.driver', 'fake');
        $user = User::factory()
            ->fullyVerified()
            ->withGlobalRole(GlobalRole::ADMINISTRATOR)
            ->create();
        // post otp code
        $this->beUser($user, false)
            ->fromRoute('one-time-password.authenticate')
            ->postByRoute('one-time-password.authenticate', data: ['code' => $code])
            ->assertRedirectToRoute(RouteName::ADMIN_SHOW);
    }

    public function testUserWithoutDepartmentRolesRedirectsToProfileEdit(): void
    {
        $code = sprintf('%06d', $this->faker->numberBetween(0, 999_999));
        ConfigHelper::set('auth.one_time_password.driver', 'fake');
        $user = User::factory()
            ->fullyVerified()
            ->create();
        // post otp code
        $this->beUser($user, false)
            ->fromRoute('one-time-password.authenticate')
            ->postByRoute('one-time-password.authenticate', data: ['code' => $code])
            ->assertRedirectToRoute('profile.edit');
    }
}
