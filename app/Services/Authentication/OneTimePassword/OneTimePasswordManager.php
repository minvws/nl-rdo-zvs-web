<?php

declare(strict_types=1);

namespace App\Services\Authentication\OneTimePassword;

use App\Config\Config;
use Illuminate\Support\Manager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class OneTimePasswordManager extends Manager
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function createTimedDriver(): TimedOneTimePassword
    {
        /** @var TimedOneTimePassword $timedOneTimePassword */
        $timedOneTimePassword = $this->container->get(TimedOneTimePassword::class);

        return $timedOneTimePassword;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function createFakeDriver(): FakeOneTimePassword
    {
        /** @var FakeOneTimePassword $fakeOneTimePassword */
        $fakeOneTimePassword = $this->container->get(FakeOneTimePassword::class);

        return $fakeOneTimePassword;
    }

    public function getDefaultDriver(): string
    {
        return Config::string('auth.one_time_password.driver');
    }
}
