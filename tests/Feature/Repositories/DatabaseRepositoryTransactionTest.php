<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Repositories\DatabaseRepositoryTransaction;
use App\Repositories\RepositoryTransactionException;
use Exception;
use Illuminate\Database\DatabaseManager;
use Mockery\MockInterface;
use Tests\Feature\FeatureTestCase;

class DatabaseRepositoryTransactionTest extends FeatureTestCase
{
    public function testBegin(): void
    {
        $databaseManager = $this->mock(DatabaseManager::class, static function (MockInterface $mock): void {
            $mock->shouldReceive('beginTransaction')
                ->once();
        });

        /** @var DatabaseRepositoryTransaction $databaseRepositoryTransaction */
        $databaseRepositoryTransaction = $this->app->make(DatabaseRepositoryTransaction::class, [
            'databaseManager' => $databaseManager,
        ]);

        $databaseRepositoryTransaction->begin();
    }

    public function testBeginFails(): void
    {
        $databaseManager = $this->mock(DatabaseManager::class, static function (MockInterface $mock): void {
            $mock->shouldReceive('beginTransaction')
                ->once()
                ->andThrow(new Exception());
        });

        /** @var DatabaseRepositoryTransaction $databaseRepositoryTransaction */
        $databaseRepositoryTransaction = $this->app->make(DatabaseRepositoryTransaction::class, [
            'databaseManager' => $databaseManager,
        ]);

        $this->expectException(RepositoryTransactionException::class);
        $databaseRepositoryTransaction->begin();
    }

    public function testCommit(): void
    {
        $databaseManager = $this->mock(DatabaseManager::class, static function (MockInterface $mock): void {
            $mock->shouldReceive('commit')
                ->once();
        });

        /** @var DatabaseRepositoryTransaction $databaseRepositoryTransaction */
        $databaseRepositoryTransaction = $this->app->make(DatabaseRepositoryTransaction::class, [
            'databaseManager' => $databaseManager,
        ]);

        $databaseRepositoryTransaction->commit();
    }

    public function testCommitFails(): void
    {
        $databaseManager = $this->mock(DatabaseManager::class, static function (MockInterface $mock): void {
            $mock->shouldReceive('commit')
                ->once()
                ->andThrow(new Exception());
        });

        /** @var DatabaseRepositoryTransaction $databaseRepositoryTransaction */
        $databaseRepositoryTransaction = $this->app->make(DatabaseRepositoryTransaction::class, [
            'databaseManager' => $databaseManager,
        ]);

        $this->expectException(RepositoryTransactionException::class);
        $databaseRepositoryTransaction->commit();
    }

    public function testRollback(): void
    {
        $databaseManager = $this->mock(DatabaseManager::class, static function (MockInterface $mock): void {
            $mock->shouldReceive('rollback')
                ->once();
        });

        /** @var DatabaseRepositoryTransaction $databaseRepositoryTransaction */
        $databaseRepositoryTransaction = $this->app->make(DatabaseRepositoryTransaction::class, [
            'databaseManager' => $databaseManager,
        ]);

        $databaseRepositoryTransaction->rollback();
    }

    public function testRollbackFails(): void
    {
        $databaseManager = $this->mock(DatabaseManager::class, static function (MockInterface $mock): void {
            $mock->shouldReceive('rollback')
                ->once()
                ->andThrow(new Exception());
        });

        /** @var DatabaseRepositoryTransaction $databaseRepositoryTransaction */
        $databaseRepositoryTransaction = $this->app->make(DatabaseRepositoryTransaction::class, [
            'databaseManager' => $databaseManager,
        ]);

        $this->expectException(RepositoryTransactionException::class);
        $databaseRepositoryTransaction->rollback();
    }

    public function testTransaction(): void
    {
        $closure = function (): void {
        };

        $databaseManager = $this->mock(DatabaseManager::class, static function (MockInterface $mock) use ($closure): void {
            $mock->shouldReceive('transaction')
                ->once()
                ->with($closure);
        });

        /** @var DatabaseRepositoryTransaction $databaseRepositoryTransaction */
        $databaseRepositoryTransaction = $this->app->make(DatabaseRepositoryTransaction::class, [
            'databaseManager' => $databaseManager,
        ]);

        $databaseRepositoryTransaction->transaction($closure);
    }

    public function testTransactionFails(): void
    {
        $databaseManager = $this->mock(DatabaseManager::class, static function (MockInterface $mock): void {
            $mock->shouldReceive('transaction')
                ->once()
                ->andThrow(new Exception());
        });

        /** @var DatabaseRepositoryTransaction $databaseRepositoryTransaction */
        $databaseRepositoryTransaction = $this->app->make(DatabaseRepositoryTransaction::class, [
            'databaseManager' => $databaseManager,
        ]);

        $this->expectException(RepositoryTransactionException::class);
        $databaseRepositoryTransaction->transaction(function (): void {
        });
    }
}
