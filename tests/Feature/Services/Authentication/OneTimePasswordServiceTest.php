<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Authentication;

use App\Models\User;
use App\Services\Authentication\OneTimePassword\FakeOneTimePassword;
use App\Services\Authentication\OneTimePassword\OneTimePasswordInterface;
use App\Services\Authentication\OneTimePasswordService;
use Mockery;
use Mockery\MockInterface;
use Tests\Feature\FeatureTestCase;

class OneTimePasswordServiceTest extends FeatureTestCase
{
    public function testGenerateQRCodeInline(): void
    {
        $oneTimePasswordService = $this->getOneTimePasswordService();
        $user = User::factory()->fullyVerified()->create();

        $qrCode = $oneTimePasswordService->generateQRCodeInline($user);
        $this->assertStringStartsWith('<svg', $qrCode);
    }

    public function testGenerateQRCodeInlineWhenNoSecret(): void
    {
        $oneTimePasswordService = $this->getOneTimePasswordService();
        $user = User::factory()->state(['otp_secret' => null])->create();

        $qrCode = $oneTimePasswordService->generateQRCodeInline($user);
        $this->assertNull($qrCode);
    }

    public function testVerifyCode(): void
    {
        $user = User::factory()->fullyVerified()->state(['otp_verified_at' => null])->create();

        $oneTimePasswordService = $this->getOneTimePasswordService();
        $result = $oneTimePasswordService->verifyCode($this->faker->word(), $user);

        $this->assertTrue($result);
    }

    public function testVerifyCodeIfUserHasNone(): void
    {
        $user = User::factory()->fullyVerified()->otpDisabled()->create();

        $oneTimePasswordService = $this->getOneTimePasswordService();
        $result = $oneTimePasswordService->verifyCode($this->faker->word(), $user);

        $this->assertFalse($result);
    }

    public function testVerifyCodeWithInvalidCode(): void
    {
        $user = User::factory()->fullyVerified()->state(['otp_verified_at' => null])->create();

        /** @var OneTimePasswordInterface&MockInterface $oneTimePassword */
        $oneTimePassword = Mockery::mock(FakeOneTimePassword::class);
        $oneTimePassword->expects('isCodeValid')->andReturn(false);

        $oneTimePasswordService = $this->getOneTimePasswordService($oneTimePassword);
        $result = $oneTimePasswordService->verifyCode($this->faker->word(), $user);

        $this->assertFalse($result);
    }

    public function testHasOtpVerified(): void
    {
        $user = User::factory()->fullyVerified()->create();

        $oneTimePasswordService = $this->getOneTimePasswordService();
        $result = $oneTimePasswordService->hasOtpVerified($user);

        $this->assertTrue($result);
    }

    public function testHasOtpVerifiedWhenNoOtpSecret(): void
    {
        $user = User::factory()->fullyVerified()->state(['otp_secret' => null])->create();

        $oneTimePasswordService = $this->getOneTimePasswordService();
        $result = $oneTimePasswordService->hasOtpVerified($user);

        $this->assertFalse($result);
    }

    public function testHasOtpVerifiedWhenNoOtpVerifiedAt(): void
    {
        $user = User::factory()->fullyVerified()->state(['otp_verified_at' => null])->create();

        $oneTimePasswordService = $this->getOneTimePasswordService();
        $result = $oneTimePasswordService->hasOtpVerified($user);

        $this->assertFalse($result);
    }

    private function getOneTimePasswordService(?OneTimePasswordInterface $oneTimePassword = null): OneTimePasswordService
    {
        if ($oneTimePassword === null) {
            $oneTimePassword = $this->app->get(FakeOneTimePassword::class);
        }

        return $this->app->make(OneTimePasswordService::class, [
            'oneTimePassword' => $oneTimePassword,
        ]);
    }
}
