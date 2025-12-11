<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Authentication\OneTimePassword;

use App\Services\Authentication\OneTimePassword\OneTimePasswordManager;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

class OneTimePasswordManagerTest extends FeatureTestCase
{
    public function testDefaultDriverFromConfig(): void
    {
        $driver = $this->faker->word();
        ConfigHelper::set('auth.one_time_password.driver', $driver);

        $oneTimePasswordManager = $this->app->get(OneTimePasswordManager::class);

        $this->assertSame($driver, $oneTimePasswordManager->getDefaultDriver());
    }
}
