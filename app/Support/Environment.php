<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\App;
use RuntimeException;

use function is_bool;

class Environment
{
    public function isDevelopment(): bool
    {
        return $this->isEnvironment(['dev', 'development', 'local']);
    }

    public function isProduction(): bool
    {
        return $this->isEnvironment(['production']);
    }

    public function isTesting(): bool
    {
        return $this->isEnvironment(['test', 'testing']);
    }

    public function isDevelopmentOrTesting(): bool
    {
        if ($this->isDevelopment()) {
            return true;
        }

        return $this->isTesting();
    }

    /**
     * @param array<string> $environmentNames
     */
    private function isEnvironment(array $environmentNames): bool
    {
        /** @var string|bool $environment */
        $environment = App::environment($environmentNames);

        if (!is_bool($environment)) {
            throw new RuntimeException('Unable to determine environment');
        }

        return $environment;
    }
}
