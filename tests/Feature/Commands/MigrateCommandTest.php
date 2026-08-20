<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Console\Commands\MigrateCommand;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Sleep;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

use function app;
use function config;
use function sprintf;

/**
 * Extends TestCase instead of FeatureTestCase because this will not work with database-transactions.
 *
 * Runs against a throwaway database so the destructive "db:wipe"/"migrate" calls can't drop tables on
 * the shared "testing" database that other non-transactional tests read at the same time.
 */
class MigrateCommandTest extends TestCase
{
    private string $originalDatabase;
    private string $isolatedDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDatabase = (string) config('database.connections.pgsql.database');
        $this->isolatedDatabase = $this->originalDatabase . '_migrate_command_' . (ParallelTesting::token() ?: '0');

        DB::unprepared(sprintf('DROP DATABASE IF EXISTS "%s" WITH (FORCE)', $this->isolatedDatabase));
        DB::unprepared(sprintf('CREATE DATABASE "%s"', $this->isolatedDatabase));

        config(['database.connections.pgsql.database' => $this->isolatedDatabase]);
        DB::purge('pgsql');
    }

    protected function tearDown(): void
    {
        config(['database.connections.pgsql.database' => $this->originalDatabase]);
        DB::purge('pgsql');

        DB::unprepared(sprintf('DROP DATABASE IF EXISTS "%s" WITH (FORCE)', $this->isolatedDatabase));

        parent::tearDown();
    }

    public function testCommand(): void
    {
        $this->artisan('migrate')
            ->assertSuccessful()
            ->expectsOutputToContain('Migrations done');
    }

    public function testCommandOnAWipedDatabase(): void
    {
        $this->artisan('db:wipe');

        $this->artisan('migrate')
            ->assertSuccessful()
            ->expectsOutputToContain('Migrations done');
    }

    public function testCommandSkipsMigrationsThatAreAlreadyApplied(): void
    {
        $this->artisan('migrate')->assertSuccessful();

        $this->artisan('migrate')
            ->assertSuccessful()
            ->expectsOutputToContain('Migrations done');
    }

    public function testRunsOperationOnce(): void
    {
        $attempts = 0;

        $this->executeWithConcurrencyRetries(static function () use (&$attempts): void {
            $attempts++;
        });

        $this->assertSame(1, $attempts);
    }

    public function testRetriesConcurrencyConflictUntilItSucceeds(): void
    {
        $attempts = 0;

        $this->executeWithConcurrencyRetries(function () use (&$attempts): void {
            $attempts++;

            if ($attempts < 3) {
                throw $this->concurrencyConflict();
            }
        });

        $this->assertSame(3, $attempts);
    }

    public function testStopsRetryingAfterTheMaximumNumberOfAttempts(): void
    {
        $attempts = 0;

        $this->expectException(QueryException::class);

        try {
            $this->executeWithConcurrencyRetries(function () use (&$attempts): void {
                $attempts++;

                throw $this->concurrencyConflict();
            });
        } finally {
            $this->assertSame(10, $attempts);
        }
    }

    public function testDoesNotRetryOtherQueryExceptions(): void
    {
        $attempts = 0;

        $this->expectException(QueryException::class);

        try {
            $this->executeWithConcurrencyRetries(function () use (&$attempts): void {
                $attempts++;

                throw new QueryException('pgsql', 'select 1', [], new RuntimeException('deadlock detected'));
            });
        } finally {
            $this->assertSame(1, $attempts);
        }
    }

    /**
     * @param callable(): void $operation
     */
    private function executeWithConcurrencyRetries(callable $operation): void
    {
        Sleep::fake();

        $method = new ReflectionMethod(MigrateCommand::class, 'executeWithConcurrencyRetries');
        $method->invoke(app(MigrateCommand::class), $operation);
    }

    private function concurrencyConflict(): QueryException
    {
        return new QueryException(
            'pgsql',
            'alter role cts',
            [],
            new Exception('SQLSTATE[XX000]: Internal error: 7 ERROR:  tuple concurrently updated'),
        );
    }
}
