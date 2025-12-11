<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Petition;

use function __;

class PetitionGenericExcelExport extends PetitionAbstractExcelExport
{
    /**
     * @param Petition $row
     *
     * @return array<int, string|null>
     */
    public function map(mixed $row): array
    {
        return [
            $row->number,
            $row->name ?? '-',
        ];
    }

    /**
     * @return array<string>
     */
    public function headings(): array
    {
        return [
            __('exports.reference'),
            __('exports.subject'),
        ];
    }
}
