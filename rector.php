<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\DeadCode\Rector\StmtsAwareInterface\RemoveDeadInstanceOfAssertRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/bootstrap',
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/resources',
        __DIR__ . '/routes',
        //        __DIR__ . '/tests',
        __DIR__ . '/utils',
    ])
    ->withSkip([
        __DIR__ . '/bootstrap/cache',
    ])
    ->withComposerBased(phpunit: true, laravel: true)
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        typeDeclarationDocblocks: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
    )
    ->withSkip([
        RecastingRemovalRector::class => [
            __DIR__ . '/app/ValueObjects/PetitionEventData.php',
            __DIR__ . '/app/Http/Controllers/PetitionEventWizardController.php',
            __DIR__ . '/app/Http/Requests/PetitionEvent/AddPetitionEventRequest.php',
            __DIR__ . '/app/Factories/PetitionEventDataFactory.php',
        ],
        // RectorLaravel types Request::route() as non-null, so it flags the
        // assert() as dead, but Larastan types it as Route|null and needs the
        // narrowing. Keep the assert to satisfy PHPStan.
        RemoveDeadInstanceOfAssertRector::class => [
            __DIR__ . '/app/Http/Middleware/RememberActiveDepartmentMiddleware.php',
        ],
    ]);
