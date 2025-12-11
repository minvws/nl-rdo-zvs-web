<?php

declare(strict_types=1);

namespace App\Services\Virusscanner;

use App\Config\Config;
use Illuminate\Support\Manager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class VirusscannerManager extends Manager
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function createClamavDriver(): ClamavVirusscanner
    {
        /** @var ClamavVirusscanner $clamavVirusscanner */
        $clamavVirusscanner = $this->container->get(ClamavVirusscanner::class);

        return $clamavVirusscanner;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function createFakeDriver(): FakeVirusscanner
    {
        /** @var FakeVirusscanner $fakeVirusscanner */
        $fakeVirusscanner = $this->container->get(FakeVirusscanner::class);

        return $fakeVirusscanner;
    }

    public function getDefaultDriver(): string
    {
        return Config::string('virusscanner.default');
    }
}
