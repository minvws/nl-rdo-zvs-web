<?php

declare(strict_types=1);

namespace App\Exports;

use App\Collections\CustomPetitionPropertyCollection;
use App\Config\Config;
use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Models\Petition;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Webmozart\Assert\Assert;

use function __;
use function sprintf;

class PetitionBezwaarExcelExport extends PetitionAbstractExcelExport implements WithColumnFormatting
{
    /**
     * @param Petition $row
     *
     * @return array<string|float|null>
     */
    public function map(mixed $row): array
    {
        $termEngineConverted = $row->isTermEngineConverted();

        return [
            $row->number,
            $row->name ?? '-',

            // the values below depend on Terms V1 or V2 being used
            $this->dateOfEntry($row, $termEngineConverted),
            $this->dateWithdrawn($row, $termEngineConverted),
            $this->dateDecisionOnAppeal($row, $termEngineConverted),

            $this->formatInOutTerm($row->customPetitionProperties),
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
            __('exports.date_decision'),
            __('exports.decision_within_or_outside_term'),
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

    private function formatInOutTerm(CustomPetitionPropertyCollection $customPetitionProperties): string
    {
        $optionSet = Config::arrayAllString(sprintf('export_mapping.%s.term_options', PetitionVariant::BEZWAAR->value));
        Assert::isMap($optionSet);

        return $this->formatMatchingCustomOptions($customPetitionProperties, $optionSet);
    }

    private function formatDecision(CustomPetitionPropertyCollection $customPetitionProperties): string
    {
        $optionSet = Config::arrayAllString(sprintf('export_mapping.%s.decision_options', PetitionVariant::BEZWAAR->value));
        Assert::isMap($optionSet);

        return $this->formatMatchingCustomOptions($customPetitionProperties, $optionSet);
    }

    private function dateOfEntry(Petition $row, bool $termEngineConverted): ?float
    {
        if ($termEngineConverted) {
            return ExcelDate::fromCalendarDate($row->petitionEvents
                ->firstWhere('type', PetitionEventType::RECEIPT_OF_OBJECTION)?->date);
        }

        return $this->formatDate($row->date_of_entry);
    }
}
