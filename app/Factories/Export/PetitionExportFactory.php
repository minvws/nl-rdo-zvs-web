<?php

declare(strict_types=1);

namespace App\Factories\Export;

use App\Enums\ExportType;
use App\Enums\PetitionTypeType;
use App\Exports\ExportCriteria;
use App\Exports\PetitionBeroepExcelExport;
use App\Exports\PetitionBezwaarExcelExport;
use App\Exports\PetitionExportInterface;
use App\Exports\PetitionInternalExcelExport;
use App\Exports\PetitionWooVerzoekExcelExport;

use function __;

class PetitionExportFactory
{
    public function create(ExportCriteria $criteria): PetitionExportInterface
    {
        return match (true) {
            $criteria->exportType === ExportType::INTERNAL => new PetitionInternalExcelExport($criteria),
            $criteria->petitionType->type === PetitionTypeType::BEROEP => new PetitionBeroepExcelExport(
                $this->getTabNameTranslation($criteria->petitionType->type),
                $criteria,
            ),
            $criteria->petitionType->type === PetitionTypeType::BEZWAAR => new PetitionBezwaarExcelExport(
                $this->getTabNameTranslation($criteria->petitionType->type),
                $criteria,
            ),
            $criteria->petitionType->type === PetitionTypeType::WOO_VERZOEK => new PetitionWooVerzoekExcelExport(
                $this->getTabNameTranslation($criteria->petitionType->type),
                $criteria,
            ),
        };
    }

    private function getTabNameTranslation(PetitionTypeType $petitionTypeType): string
    {
        return __('exports.tab_names.' . $petitionTypeType->value);
    }
}
