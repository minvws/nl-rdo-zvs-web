<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PetitionInternalExcelExport implements WithMultipleSheets, PetitionExportInterface
{
    use Exportable;

    public function __construct(
        private readonly ExportCriteria $criteria,
    ) {
    }

    /**
     * @return array<int, mixed>
     */
    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new PetitionInternalExcelExportPetitionSheet($this->criteria);
        $sheets[] = new PetitionInternalExcelExportStatusHistorySheet($this->criteria);

        return $sheets;
    }

    public function writeToDisk(string $path, string $disk): void
    {
        $this->store($path, $disk);
    }
}
