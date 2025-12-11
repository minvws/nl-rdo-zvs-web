<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /** @var array<class-string, array<class-string>> */
    protected array $listen = [];

    public function boot(): void
    {
    }
}
