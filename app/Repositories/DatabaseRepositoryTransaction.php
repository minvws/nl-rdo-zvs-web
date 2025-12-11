<?php

declare(strict_types=1);

namespace App\Repositories;

use Closure;
use Illuminate\Database\DatabaseManager;
use Throwable;

readonly class DatabaseRepositoryTransaction implements RepositoryTransactionInterface
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @throws RepositoryTransactionException
     */
    public function begin(): void
    {
        try {
            $this->databaseManager->beginTransaction();
        } catch (Throwable $throwable) {
            throw RepositoryTransactionException::fromThrowable($throwable);
        }
    }

    /**
     * @throws RepositoryTransactionException
     */
    public function commit(): void
    {
        try {
            $this->databaseManager->commit();
        } catch (Throwable $throwable) {
            throw RepositoryTransactionException::fromThrowable($throwable);
        }
    }

    /**
     * @throws RepositoryTransactionException
     */
    public function rollback(): void
    {
        try {
            $this->databaseManager->rollBack();
        } catch (Throwable $throwable) {
            throw RepositoryTransactionException::fromThrowable($throwable);
        }
    }

    /**
     * @param Closure(): void $callback
     *
     * @throws RepositoryTransactionException
     */
    public function transaction(Closure $callback): void
    {
        try {
            $this->databaseManager->transaction($callback);
        } catch (Throwable $throwable) {
            throw RepositoryTransactionException::fromThrowable($throwable);
        }
    }
}
