<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Authentication\OneTimePassword;

use App\Services\Authentication\OneTimePassword\OneTimePasswordInterface;
use App\Services\Authentication\OneTimePassword\TimedOneTimePassword;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

class TimedOneTimePasswordTest extends FeatureTestCase
{
    public function testInstance(): void
    {
        ConfigHelper::set('auth.one_time_password.driver', 'timed');

        $oneTimePassword = $this->app->get(OneTimePasswordInterface::class);

        $this->assertInstanceOf(TimedOneTimePassword::class, $oneTimePassword);
    }

    public function testIsCodeValid(): void
    {
        $timedOneTimePassword = $this->app->get(TimedOneTimePassword::class);
        $isCodeValid = $timedOneTimePassword->isCodeValid($this->faker->word(), $this->faker->word());

        $this->assertFalse($isCodeValid);
    }

    public function testGenerateQRCodeInline(): void
    {
        $timedOneTimePassword = $this->app->get(TimedOneTimePassword::class);
        $qrCode = $timedOneTimePassword->generateQRCodeInline($this->faker->word(), $this->faker->word());

        $this->assertStringStartsWith('<svg', $qrCode);
        $this->assertStringEndsWith('</svg>', $qrCode);
    }
}
