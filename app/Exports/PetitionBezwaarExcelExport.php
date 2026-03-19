<?php

declare(strict_types=1);

namespace App\Exports;

use App\Collections\CustomPetitionPropertyCollection;
use App\Config\Config;
use App\Enums\CustomDateLabel;
use App\Enums\PetitionEventType;
use App\Enums\PetitionTypeType;
use App\Enums\ResultType;
use App\Models\Petition;
use Webmozart\Assert\Assert;

use function __;
use function sprintf;

class PetitionBezwaarExcelExport extends PetitionAbstractExcelExport
{
    /**
     * @param Petition $row
     *
     * @return array<string|null>
     */
    public function map(mixed $row): array
    {
        return [
            $row->number,
            $row->name ?? '-',

            // the values below depend on Terms V1 or V2 being used
            $this->dateOfEntry($row),
            $this->dateWithdrawn($row),
            $this->dateDecision($row),

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

    private function formatInOutTerm(CustomPetitionPropertyCollection $customPetitionProperties): string
    {
        $optionSet = Config::arrayAllString(sprintf('export_mapping.%s.term_options', PetitionTypeType::BEZWAAR->value));
        Assert::isMap($optionSet);

        return $this->formatMatchingCustomOptions($customPetitionProperties, $optionSet);
    }

    private function formatDecision(CustomPetitionPropertyCollection $customPetitionProperties): string
    {
        $optionSet = Config::arrayAllString(sprintf('export_mapping.%s.decision_options', PetitionTypeType::BEZWAAR->value));
        Assert::isMap($optionSet);

        return $this->formatMatchingCustomOptions($customPetitionProperties, $optionSet);
    }

    private function dateOfEntry(Petition $row): ?string
    {
        if ($row->isTermEngineConverted()) {
            return $row->petitionEvents
                ->firstWhere('type', PetitionEventType::RECEIPT_OF_OBJECTION)?->date->toDateString() ?? null;
        }

        return $this->formatDate($row->date_of_entry);
    }

    private function dateWithdrawn(Petition $row): ?string
    {
        if ($row->isTermEngineConverted()) {
            return $row->petitionEvents
                ->where('type', PetitionEventType::FINAL_RESULT)
                ->where('result_type', ResultType::WITHDRAWN)
                ->first()?->date->toDateString() ?? null;
        }

        return $this->formatCustomDateValueByLabel($row->customDates, CustomDateLabel::DATE_WITHDRAWN);
    }

    private function dateDecision(Petition $row): ?string
    {
        if ($row->isTermEngineConverted()) {
            return $row->petitionEvents
                ->where('type', PetitionEventType::FINAL_RESULT)
                ->where('result_type', ResultType::FINAL_DECISION)
                ->first()?->date->toDateString() ?? null;
        }

        return $this->formatCustomDateValueByLabel($row->customDates, CustomDateLabel::DATE_DECISION_ON_APPEAL);
    }
}
