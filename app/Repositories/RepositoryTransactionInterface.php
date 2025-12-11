<?php

declare(strict_types=1);

namespace App\Repositories;

use Closure;

interface RepositoryTransactionInterface
{
    /**
     * @throws RepositoryTransactionException
     */
    public function begin(): void;

    /**
     * @throws RepositoryTransactionException
     */
    public function commit(): void;

    public function rollback(): void;

    /**
     * @throws RepositoryTransactionException
     */
    public function transaction(Closure $callback): void;
}
