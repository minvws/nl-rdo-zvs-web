<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Database\Console\Migrations\MigrateCommand as IlluminateMigrateCommand;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Override;
use stdClass;
use Throwable;
use ValueError;
use Webmozart\Assert\Assert;

use function collect;
use function sprintf;
use function version_compare;

class MigrateCommand extends IlluminateMigrateCommand
{
    /**
     * Postgres roles are cluster-global, so parallel test workers recreating their own databases can
     * write the same "cts"/"cts_dba" catalog rows at once and fail with "tuple concurrently updated".
     * That conflict is transient, so retry the affected migration file a few times.
     */
    private const int MAX_CONCURRENCY_RETRIES = 10;
    private const string CONCURRENCY_CONFLICT_MESSAGE = 'tuple concurrently updated';

    /**
     * @throws ValueError|Throwable
     */
    #[Override]
    public function handle(): int
    {
        $table = 'migrations';

        try {
            $databaseMigrations = DB::table($table)->get();
            $batch = $databaseMigrations->max('batch') + 1;
        } catch (QueryException) {
            $databaseMigrations = new Collection();
            $batch = 1;
        }

        $fileSystem = Storage::disk('sql');
        $sqlFiles = $fileSystem->allFiles();
        $versionedFiles = $this->getVersionedFiles($sqlFiles);

        foreach ($versionedFiles as $migrations) {
            foreach ($migrations as $migration) {
                if (
                    $databaseMigrations->contains(static function (stdClass $databaseMigration) use ($migration): bool {
                        return $databaseMigration->migration === $migration;
                    })
                ) {
                    continue;
                }

                $query = $fileSystem->get($migration);
                Assert::string($query);

                $this->executeWithConcurrencyRetries(static function () use ($query): void {
                    DB::transaction(static function () use ($query): void {
                        DB::unprepared($query);
                    });
                });
                DB::table($table)->insert([
                    'migration' => $migration,
                    'batch' => $batch,
                ]);

                $this->components->task(sprintf('Executed %s', $migration));
            }
        }

        $this->output->success('Migrations done');

        return self::SUCCESS;
    }

    /**
     * @param callable(): void $operation
     *
     * @throws Throwable
     */
    protected function executeWithConcurrencyRetries(callable $operation): void
    {
        $attempt = 1;

        while (true) {
            try {
                $operation();

                return;
            } catch (QueryException $exception) {
                if ($attempt >= self::MAX_CONCURRENCY_RETRIES || !$this->isConcurrencyConflict($exception)) {
                    throw $exception;
                }

                Sleep::usleep($attempt * 20_000);
                $attempt++;
            }
        }
    }

    private function isConcurrencyConflict(QueryException $exception): bool
    {
        return Str::contains($exception->getMessage(), self::CONCURRENCY_CONFLICT_MESSAGE);
    }

    /**
     * @param array<string> $files
     *
     * @return array<string, array<int, string>>
     *
     * @throws ValueError
     */
    public function getVersionedFiles(array $files): array
    {
        return collect($files)
            ->filter(static function (string $file): bool {
                return Str::of($file)->endsWith('.sql');
            })
            ->groupBy(static function (string $file): string {
                return Str::of($file)->before('/')->toString();
            })
            // @phpstan-ignore-next-line
            ->sortKeysUsing(version_compare(...))
            ->map(static function (Collection $group): array {
                return $group->values()->all();
            })
            ->all();
    }
}
