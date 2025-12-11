<?php

declare(strict_types=1);

namespace App\Services\Decision;

use App\Enums\RouteName;
use App\Services\AbstractFilterService;

final readonly class DecisionFilterService extends AbstractFilterService
{
    protected function getFilterContext(): string
    {
        return 'decision';
    }

    protected function getIndexRouteName(): RouteName
    {
        return RouteName::DEPARTMENTS_DECISIONS_INDEX;
    }
}
