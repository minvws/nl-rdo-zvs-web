<?php

declare(strict_types=1);

arch('Global architecture rules')
    ->expect('App')
    ->toUseStrictTypes()
    ->not->toUse(['die', 'dd', 'dump'])
    ->and('App')
    ->interfaces()
    ->toHaveSuffix('Interface')
    ->and('App')
    ->classes()
    ->not->toHaveSuffix('Interface');
