<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Authentication\OneTimePassword;

use App\Services\Authentication\OneTimePassword\FakeOneTimePassword;
use App\Services\Authentication\OneTimePassword\OneTimePasswordInterface;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

class FakeOneTimePasswordTest extends FeatureTestCase
{
    public function testInstance(): void
    {
        ConfigHelper::set('auth.one_time_password.driver', 'fake');

        $oneTimePassword = $this->app->get(OneTimePasswordInterface::class);

        $this->assertInstanceOf(FakeOneTimePassword::class, $oneTimePassword);
    }

    public function testIsValid(): void
    {
        $fakeOneTimePassword = $this->app->get(FakeOneTimePassword::class);
        $isCodeValid = $fakeOneTimePassword->isCodeValid($this->faker->word(), $this->faker->word());

        $this->assertTrue($isCodeValid);
    }
}
