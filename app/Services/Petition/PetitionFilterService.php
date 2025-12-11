<?php

declare(strict_types=1);

namespace App\Services\Petition;

use App\Enums\RouteName;
use App\Services\AbstractFilterService;

final readonly class PetitionFilterService extends AbstractFilterService
{
    protected function getFilterContext(): string
    {
        return 'petition';
    }

    protected function getIndexRouteName(): RouteName
    {
        return RouteName::DEPARTMENTS_PETITIONS_INDEX;
    }
}
