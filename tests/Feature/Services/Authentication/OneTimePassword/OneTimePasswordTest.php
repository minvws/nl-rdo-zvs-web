<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Authentication\OneTimePassword;

use App\Services\Authentication\OneTimePassword\OneTimePasswordInterface;
use Tests\Feature\FeatureTestCase;

class OneTimePasswordTest extends FeatureTestCase
{
    public function testInstance(): void
    {
        $oneTimePassword = $this->app->get(OneTimePasswordInterface::class);

        $this->assertInstanceOf(OneTimePasswordInterface::class, $oneTimePassword);
    }
}
