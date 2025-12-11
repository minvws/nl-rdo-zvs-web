<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use Tests\TestCase;

/**
 * Extends TestCase instead of FeatureTestCase because this will not work with database-transactions
 */
class MigrateCommandTest extends TestCase
{
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
}
