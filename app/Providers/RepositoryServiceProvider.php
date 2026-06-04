<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\DatabaseRepositoryTransaction;
use App\Repositories\RepositoryTransactionInterface;
use App\Repositories\WordTemplate\FilesystemWordTemplateRepository;
use App\Repositories\WordTemplate\WordTemplateRepositoryInterface;
use Illuminate\Support\ServiceProvider;
use Override;

class RepositoryServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->registerDatabase();
        $this->registerFilesystem();
    }

    private function registerDatabase(): void
    {
        $this->app->singleton(RepositoryTransactionInterface::class, DatabaseRepositoryTransaction::class);
    }

    private function registerFilesystem(): void
    {
        $this->app->singleton(WordTemplateRepositoryInterface::class, FilesystemWordTemplateRepository::class);
    }
}
