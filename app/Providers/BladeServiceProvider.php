<?php

declare(strict_types=1);

namespace App\Providers;

use App\View\HtmxHelper;
use Closure;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    public function boot(
        HtmxHelper $htmxHelper,
        Request $request,
        Vite $vite,
    ): void {
        Blade::if('ifHtmx', $this->buildIfHtmx($htmxHelper, $request));
        Blade::if('ifNotHtmx', $this->buildIfNotHtmx($htmxHelper, $request));

        $vite->useCspNonce();
    }

    private function buildIfHtmx(HtmxHelper $htmxHelper, Request $request): Closure
    {
        return static function () use ($htmxHelper, $request): bool {
            return $htmxHelper->isHtmxRequest($request);
        };
    }

    private function buildIfNotHtmx(HtmxHelper $htmxHelper, Request $request): Closure
    {
        return static function () use ($htmxHelper, $request): bool {
            return !$htmxHelper->isHtmxRequest($request);
        };
    }
}
