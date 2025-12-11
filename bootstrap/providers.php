<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\AuthenticationServiceProvider;
use App\Providers\AuthorizationServiceProvider;
use App\Providers\BladeServiceProvider;
use App\Providers\DatabaseServiceProvider;
use App\Providers\DateServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\FakerServiceProvider;
use App\Providers\FilesystemServiceProvider;
use App\Providers\LegalTermDeadlineCalculatorServiceProvider;
use App\Providers\RepositoryServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Providers\ValidationServiceProvider;
use App\Providers\ViewComponentServiceProvider;
use App\Providers\ViewFactoryServiceProvider;

return [
    AppServiceProvider::class,
    AuthenticationServiceProvider::class,
    AuthorizationServiceProvider::class,
    BladeServiceProvider::class,
    DateServiceProvider::class,
    DatabaseServiceProvider::class,
    EventServiceProvider::class,
    FakerServiceProvider::class,
    FilesystemServiceProvider::class,
    RepositoryServiceProvider::class,
    RouteServiceProvider::class,
    ViewComponentServiceProvider::class,
    ViewFactoryServiceProvider::class,
    LegalTermDeadlineCalculatorServiceProvider::class,
    ValidationServiceProvider::class,
];
