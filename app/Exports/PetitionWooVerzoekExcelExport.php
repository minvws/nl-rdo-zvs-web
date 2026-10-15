<?php

declare(strict_types=1);

namespace App\Exports;

use App\Collections\CustomPetitionPropertyCollection;
use App\Collections\PetitionTermCollection;
use App\Config\Config;
use App\Config\DepartmentConfigurationService;
use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Enums\UnspecifiedAdjournmentMode;
use App\Factories\WizardEventCollectionFactory;
use App\Models\Petition;
use App\Models\PetitionTerm;
use App\Services\DerivedState;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Webmozart\Assert\Assert;

use function __;
use function resolve;
use function sprintf;

class PetitionWooVerzoekExcelExport extends PetitionAbstractExcelExport implements WithColumnFormatting
{
    /**
     * @param Petition $row
     *
     * @return array<int|string|float|null>
     */
    public function map(mixed $row): array
    {
        $termEngineConverted = $row->isTermEngineConverted();

        return [
            $row->number,
            $row->name ?? '-',

            // the values below depend on Terms V1 or V2 being used
            $this->dateOfEntry($row, $termEngineConverted),
            $this->reasonResultWithoutDecision($row, $termEngineConverted),
            $this->dateSettlementWithoutDecision($row, $termEngineConverted),
            $this->dateOfDecision($row, $termEngineConverted),
            $this->totalDaysOfSuspensions($row, $termEngineConverted),
            $this->inAgreementWithApplicant($row, $termEngineConverted),
            $this->dateAgreementWithApplicant($row, $termEngineConverted),
            $this->adjourned($row, $termEngineConverted),
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

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'C' => ExcelDate::DATE_FORMAT,
            'E' => ExcelDate::DATE_FORMAT,
            'F' => ExcelDate::DATE_FORMAT,
            'I' => ExcelDate::DATE_FORMAT,
        ];
    }

    private function getThirdTermEndDate(PetitionTermCollection $terms): ?float
    {
        if ($terms->hasThirdTerm()) {
            $term = $terms->getThirdTerm();
            Assert::isInstanceOf($term, PetitionTerm::class);

            return ExcelDate::fromCalendarDate($term->end_date);
        }

        return null;
    }

    private function dateOfEntry(Petition $row, bool $termEngineConverted): ?float
    {
        if ($termEngineConverted) {
            return ExcelDate::fromCalendarDate($row->petitionEvents
                ->firstWhere('type', PetitionEventType::PETITION_RECEIVED)?->date);
        }

        return $this->formatDate($row->date_of_entry);
    }

    private function reasonResultWithoutDecision(Petition $row, bool $termEngineConverted): string
    {
        if ($termEngineConverted) {
            $resultType = $row->petitionEvents->finalResultWithoutDecision()?->result_type;

            if ($resultType !== null) {
                return __(
                    sprintf('exports.%s.%s', PetitionVariant::WOO_VERZOEK->value, $resultType->value),
                );
            }
        }

        return $this->formatReasonV1($row->customPetitionProperties);
    }

    private function formatReasonV1(CustomPetitionPropertyCollection $customPetitionProperties): string
    {
        $optionSet = Config::arrayAllString(sprintf('export_mapping.%s.reason_options', PetitionVariant::WOO_VERZOEK->value));
        Assert::isMap($optionSet);

        return $this->formatMatchingCustomOptions($customPetitionProperties, $optionSet);
    }

    /**
     * The column adds up every form of suspension: "preciseren" and "zienswijze" through the
     * letters of suspension, plus the days an unspecified adjournment kept the term frozen. An
     * adjournment without an end date runs on until today, so its days are cut off at the end of
     * the period the export was asked for. Departments whose terms keep running through an
     * adjournment have no frozen days to add.
     */
    private function totalDaysOfSuspensions(Petition $row, bool $termEngineConverted): int
    {
        if ($termEngineConverted) {
            $mode = resolve(DepartmentConfigurationService::class)->unspecifiedAdjournmentMode($row->department);

            // we have to build the calendar to determine this value
            $events = WizardEventCollectionFactory::fromModels($row->petitionEvents);
            $calendar = new DerivedState()
                ->withUnspecifiedAdjournmentMode($mode)
                ->addEvents($events->all())
                ->buildCalendar()
                ->getCalendar();

            if ($mode === UnspecifiedAdjournmentMode::CONTINUES_TERMS) {
                return $calendar->totalDaysSuspended;
            }

            return $calendar->totalDaysSuspended
                + $calendar->totalDaysAdjournedUpTo($this->criteria->dateRange->getEndDate());
        }

        return $row->petitionTerms->totalDaysOfSuspensions();
    }

    private function inAgreementWithApplicant(Petition $row, bool $termEngineConverted): string
    {
        if ($termEngineConverted) {
            return $row->petitionEvents
                ->where('type', PetitionEventType::MEETING_SCHEDULED)
                ->first() ? __('exports.true') : __('exports.false');
        }

        return $row->petitionTerms->hasThirdTerm() ? __('exports.true') : __('exports.false');
    }

    private function dateAgreementWithApplicant(Petition $row, bool $termEngineConverted): ?float
    {
        if ($termEngineConverted) {
            return ExcelDate::fromCalendarDate($row->petitionEvents
                ->where('type', PetitionEventType::MEETING_SCHEDULED)
                ->first()?->date);
        }

        return $this->getThirdTermEndDate($row->petitionTerms);
    }

    private function adjourned(Petition $row, bool $termEngineConverted): string
    {
        if ($termEngineConverted) {
            return $row->petitionEvents
                ->where('type', PetitionEventType::ADJOURNMENT)
                ->first() ? __('exports.true') : __('exports.false');
        }

        return $row->petitionTerms->hasSecondTerm() ? __('exports.true') : __('exports.false');
    }
}
