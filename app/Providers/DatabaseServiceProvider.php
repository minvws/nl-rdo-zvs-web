<?php

declare(strict_types=1);

namespace App\Providers;

use App\Console\Commands\MigrateCommand;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Override;

class DatabaseServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(
            static fn(Application $application): MigrateCommand => new MigrateCommand(
                $application->make('migrator'),
                $application->make(Dispatcher::class),
            ),
        );
    }
}
