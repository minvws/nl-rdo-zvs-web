<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Models\Department;
use App\Models\User;
use App\Services\Authentication\OneTimePasswordService;
use Mockery\MockInterface;
use Tests\Feature\FeatureTestCase;

use function sprintf;

class EnforceOneTimePasswordTest extends FeatureTestCase
{
    public function testRedirectToOneTimePasswordIfOtpVerifiedButNoValidSession(): void
    {
        $user = User::factory()->fullyVerified()->create();

        $this->beUser($user, false)
            ->from('/')
            ->getByRoute('dashboard')
            ->assertRedirectToRoute('one-time-password.authenticate', ['next' => '/']);
    }

    public function testRedirectToProfileIfOtpNotVerified(): void
    {
        $user = User::factory()->fullyVerified()->state(['otp_verified_at' => null])->create();

        $this->beUser($user)
            ->getByRoute('dashboard')
            ->assertRedirectToRoute('one-time-password.enroll');
    }

    public function testAllowsAccessToEnrollPageWhenOtpNotVerified(): void
    {
        Department::factory()->create();
        $user = User::factory()->fullyVerified()->state(['otp_verified_at' => null])->create();

        $this->beUser($user)
            ->getByRoute('one-time-password.enroll')
            ->assertOk();
    }

    public function testVerifiesOtpVerification(): void
    {
        $code = sprintf('%06d', $this->faker->numberBetween(0, 999_999));
        $user = User::factory()->fullyVerified()->state(['otp_verified_at' => null])->create();

        /** @var OneTimePasswordService&MockInterface $oneTimePassword */
        $oneTimePassword = $this->mock(OneTimePasswordService::class);
        $oneTimePassword->expects('hasOtpVerified')
            ->once()
            ->andReturnFalse();
        $oneTimePassword->expects('verifyCode')
            ->once()
            ->andReturnFalse();

        $this->beUser($user)
            ->postByRoute('one-time-password.confirm', ['otp_confirmation' => $code])
            ->assertStatus(302);
    }
}
