<?php

declare(strict_types=1);

use Tests\Feature\Commands\MigrateCommandTest;
use Tests\Feature\Exports\ExportTestDataMapper;
use Tests\Feature\Exports\PetitionExcelExport;
use Tests\Feature\FeatureTestCase;

arch('Unit tests do not extend FeatureTestCase')
    ->expect('Tests\Unit')
    ->not->toExtend(FeatureTestCase::class);

arch('Feature tests do not extend TestCase')
    ->expect('Tests\Feature')
    ->toExtend(FeatureTestCase::class)
    ->ignoring([
        MigrateCommandTest::class,
        ExportTestDataMapper::class,
        PetitionExcelExport::class,
    ]);
