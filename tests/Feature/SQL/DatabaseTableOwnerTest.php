<?php

declare(strict_types=1);

namespace Tests\Feature\SQL;

use Illuminate\Support\Facades\DB;
use Tests\Feature\FeatureTestCase;

use function sprintf;

class DatabaseTableOwnerTest extends FeatureTestCase
{
    public function testTableOwnerIsCts(): void
    {
        $result = DB::select('select * from pg_tables where schemaname = ?', ['public']);

        foreach ($result as $table) {
            $expectedOwner = match ($table->tablename) {
                'migrations', 'deploy_releases' => 'cts_dba',
                default => 'cts',
            };

            $this->assertEquals(
                $expectedOwner,
                $table->tableowner,
                sprintf('Table owner of table %s is not %s', $table->tablename, $expectedOwner),
            );
        }
    }
}
