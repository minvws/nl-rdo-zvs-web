<?php

declare(strict_types=1);

namespace App\Services\Virusscanner;

use Psr\Log\LoggerInterface;

readonly class FakeVirusscanner implements VirusscannerInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function isHealthy(): bool
    {
        return true;
    }

    /**
     * @param resource $stream
     */
    public function isResourceClean($stream): bool
    {
        $this->logger->debug('fake scanner result', ['isOk', true]);

        return true;
    }
}
