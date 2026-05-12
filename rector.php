<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Set\LaravelSetList;

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
    // uncomment to reach your current PHP version
    // ->withPhpSets()
    ->withRules([
//        AddParamArrayDocblockFromDimFetchAccessRector::class,
    ])
    ->withConfiguredRule(
        EncapsedStringsToSprintfRector::class,
        [
            'always' => true,
        ],
    )
//    ->withConfiguredRule(RenameShortVariablesToTypeRector::class, [
//        'min_name_length' => 3,
//        'excluded' => ['id'],
//    ])
    ->withSets([
        SetList::PHP_80,
        SetList::PHP_81,
        SetList::PHP_82,
        SetList::PHP_83,
        LaravelSetList::LARAVEL_120,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_ARRAYACCESS_TO_METHOD_CALL,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_CONTAINER_STRING_TO_FULLY_QUALIFIED_NAME,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
        LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,
        LaravelSetList::ARRAY_STR_FUNCTIONS_TO_STATIC_CALL,
//        LaravelSetList::LARAVEL_IF_HELPERS,

//        LaravelSetList::LARAVEL_FACTORIES,
//        LaravelLevelSetList::UP_TO_LARAVEL_120,
//        LaravelSetList::LARAVEL_STATIC_TO_INJECTION,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
        typeDeclarationDocblocks: true,
    )
    ->withSkip([
        RecastingRemovalRector::class => [
            __DIR__ . '/app/ValueObjects/PetitionEventData.php',
            __DIR__ . '/app/Http/Controllers/PetitionEventWizardController.php',
            __DIR__ . '/app/Http/Requests/PetitionEvent/AddPetitionEventRequest.php',
            __DIR__ . '/app/Factories/PetitionEventDataFactory.php',
        ],
    ]);
