<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\DatabaseManager;

class DatabaseHealthService
{
    public function __construct(
        private readonly DatabaseManager $databaseManager,
    ) {
    }

    public function isHealthy(): bool
    {
        return $this->databaseManager->statement('SELECT 1');
    }
}
