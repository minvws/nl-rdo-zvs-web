<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\LegalTermDeadlineCalculator;
use Illuminate\Support\ServiceProvider;
use Override;

class LegalTermDeadlineCalculatorServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton('legal-term-deadline-calculator', static function ($app): LegalTermDeadlineCalculator {
            return new LegalTermDeadlineCalculator();
        });
    }
}
