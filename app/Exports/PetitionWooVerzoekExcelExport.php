<?php

declare(strict_types=1);

namespace App\Exports;

use App\Collections\CustomPetitionPropertyCollection;
use App\Collections\PetitionTermCollection;
use App\Config\Config;
use App\Enums\CustomDateLabel;
use App\Enums\PetitionTypeType;
use App\Models\Decision;
use App\Models\Petition;
use App\Models\PetitionTerm;
use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;

use function __;
use function sprintf;

class PetitionWooVerzoekExcelExport extends PetitionAbstractExcelExport
{
    /**
     * @param Petition $row
     *
     * @return array<string|int|null>
     */
    public function map(mixed $row): array
    {
        return [
            $row->number,
            $row->name ?? '-',
            $this->formatDate($row->date_of_entry),
            $this->formatReason($row->customPetitionProperties),
            $this->formatCustomDateValueByLabel($row->customDates, CustomDateLabel::DATE_SETTLEMENT_WITHOUT_DECISION),
            $this->getDateOfDecision($row->decisions),
            $row->petitionTerms->totalDaysOfSuspensions(),
            $row->petitionTerms->hasThirdTerm() ? __('exports.true') : __('exports.false'),
            $this->getThirdTermEndDate($row->petitionTerms),
            $row->petitionTerms->hasSecondTerm() ? __('exports.true') : __('exports.false'),
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
            __('exports.date_of_receipt'),
            __('exports.reason_for_settlement_without_decision'),
            __('exports.date_settlement_without_decision'),
            __('exports.date_decision'),
            __('exports.number_of_days_of_suspension'),
            __('exports.in_consultation_with_applicant'),
            __('exports.date_appointment_with_applicant'),
            __('exports.adjournment'),
        ];
    }

    private function formatReason(CustomPetitionPropertyCollection $customPetitionProperties): string
    {
        $optionSet = Config::arrayAllString(sprintf('export_mapping.%s.reason_options', PetitionTypeType::WOO_VERZOEK->value));
        Assert::isMap($optionSet);

        return $this->formatMatchingCustomOptions($customPetitionProperties, $optionSet);
    }

    private function getThirdTermEndDate(PetitionTermCollection $terms): ?string
    {
        if ($terms->hasThirdTerm()) {
            $term = $terms->getThirdTerm();
            Assert::isInstanceOf($term, PetitionTerm::class);

            return $term->end_date->format('Y-m-d');
        }

        return null;
    }

    /**
     * @param Collection<int, Decision> $decisions
     */
    private function getDateOfDecision(Collection $decisions): ?string
    {
        $latestDecision = $decisions->sortByDesc('date')->first();

        if ($latestDecision instanceof Decision && $latestDecision->date !== null) {
            return $latestDecision->date->format('Y-m-d');
        }

        return null;
    }
}
