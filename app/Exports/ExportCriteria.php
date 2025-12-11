<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\ExportType;
use App\Models\PetitionCategory;
use App\Models\PetitionType;
use App\ValueObjects\DateRange;

class ExportCriteria
{
    public function __construct(
        public readonly PetitionType $petitionType,
        public readonly ExportType $exportType,
        public readonly DateRange $dateRange,
        public readonly ?PetitionCategory $petitionCategory = null,
    ) {
    }
}
