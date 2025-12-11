<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\DisplayDateService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;
use Override;

class DateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Date::use(CarbonImmutable::class);
    }

    #[Override]
    public function register(): void
    {
        $this->app->when(DisplayDateService::class)
            ->needs('$displayTimezone')
            ->giveConfig('app.display_timezone');
        $this->app->when(DisplayDateService::class)
            ->needs('$locale')
            ->giveConfig('app.locale');
    }
}
