<?php

declare(strict_types=1);

namespace App\Exports;

use App\Collections\CustomPetitionPropertyCollection;
use App\Config\Config;
use App\Enums\CustomDateLabel;
use App\Enums\PetitionVariant;
use App\Models\Petition;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Webmozart\Assert\Assert;

use function __;
use function sprintf;

class PetitionBeroepExcelExport extends PetitionAbstractExcelExport implements WithColumnFormatting
{
    /**
     * @param Petition $row
     *
     * @return array<string|float|null>
     */
    public function map(mixed $row): array
    {
        return [
            $row->number,
            $row->name ?? '-',
            $this->formatDate($row->date_of_entry),
            $this->formatCustomDateValueByLabel($row->customDates, CustomDateLabel::DATE_WITHDRAWN),
            $this->formatCustomDateValueByLabel($row->customDates, CustomDateLabel::DATE_RULING),
            $this->formatDecision($row->customPetitionProperties),
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
            __('exports.date_of_entry'),
            __('exports.date_withdrawn'),
            __('exports.date_ruling'),
            __('exports.decision'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'C' => ExcelDate::DATE_FORMAT,
            'D' => ExcelDate::DATE_FORMAT,
            'E' => ExcelDate::DATE_FORMAT,
        ];
    }

    private function formatDecision(CustomPetitionPropertyCollection $customPetitionProperties): string
    {
        $optionSet = Config::arrayAllString(sprintf('export_mapping.%s.decision_options', PetitionVariant::BEROEP->value));
        Assert::isMap($optionSet);

        return $this->formatMatchingCustomOptions($customPetitionProperties, $optionSet);
    }
}
