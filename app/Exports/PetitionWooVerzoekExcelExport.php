<?php

declare(strict_types=1);

namespace App\Exports;

use App\Collections\CustomPetitionPropertyCollection;
use App\Collections\PetitionTermCollection;
use App\Config\Config;
use App\Enums\CustomDateLabel;
use App\Enums\PetitionEventType;
use App\Enums\PetitionTypeType;
use App\Enums\ResultType;
use App\Factories\WizardEventCollectionFactory;
use App\Models\Decision;
use App\Models\Petition;
use App\Models\PetitionTerm;
use App\Services\DerivedState;
use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;

use function __;
use function sprintf;

class PetitionWooVerzoekExcelExport extends PetitionAbstractExcelExport
{
    /**
     * @param Petition $row
     *
     * @return array<int|string|null>
     */
    public function map(mixed $row): array
    {
        return [
            $row->number,
            $row->name ?? '-',

            // the values below depend on Terms V1 or V2 being used
            $this->dateOfEntry($row),
            $this->reasonResultWithoutDecision($row),
            $this->dateResultWithoutDecision($row),
            $this->dateDecision($row),
            $this->totalDaysOfSuspensions($row),
            $this->inAgreementWithApplicant($row),
            $this->dateAgreementWithApplicant($row),
            $this->adjourned($row),
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

    private function dateOfEntry(Petition $row): ?string
    {
        if ($row->isTermEngineConverted()) {
            return $row->petitionEvents
                ->firstWhere('type', PetitionEventType::PETITION_RECEIVED)?->date->toDateString() ?? null;
        }

        return $this->formatDate($row->date_of_entry);
    }

    private function reasonResultWithoutDecision(Petition $row): string
    {
        if ($row->isTermEngineConverted()) {
            $event = $row->petitionEvents
                ->where('type', PetitionEventType::FINAL_RESULT)
                ->first();

            if ($event !== null && $event->result_type !== null && $event->result_type !== ResultType::FINAL_DECISION) {
                return __(
                    sprintf('exports.%s.%s', PetitionTypeType::WOO_VERZOEK->value, $event->result_type->value),
                );
            }
        }

        return $this->formatReasonV1($row->customPetitionProperties);
    }

    private function formatReasonV1(CustomPetitionPropertyCollection $customPetitionProperties): string
    {
        $optionSet = Config::arrayAllString(sprintf('export_mapping.%s.reason_options', PetitionTypeType::WOO_VERZOEK->value));
        Assert::isMap($optionSet);

        return $this->formatMatchingCustomOptions($customPetitionProperties, $optionSet);
    }

    private function dateResultWithoutDecision(Petition $row): ?string
    {
        if ($row->isTermEngineConverted()) {
            $event = $row->petitionEvents
                ->where('type', PetitionEventType::FINAL_RESULT)
                ->first();

            if ($event !== null && $event->result_type !== ResultType::FINAL_DECISION) {
                return $event->date->toDateString();
            }
        }

        return $this->formatCustomDateValueByLabel($row->customDates, CustomDateLabel::DATE_SETTLEMENT_WITHOUT_DECISION);
    }

    private function dateDecision(Petition $row): ?string
    {
        if ($row->isTermEngineConverted()) {
            return $row->petitionEvents
                ->where('type', PetitionEventType::FINAL_RESULT)
                ->where('result_type', ResultType::FINAL_DECISION)
                ->first()?->date->toDateString() ?? null;
        }

        return $this->getDateOfDecision($row->decisions);
    }

    private function totalDaysOfSuspensions(Petition $row): int
    {
        if ($row->isTermEngineConverted()) {
            // we have to build the calendar to determine this value
            $events = WizardEventCollectionFactory::fromModels($row->petitionEvents);
            $calendar = new DerivedState()->addEvents($events->all())->buildCalendar()->getCalendar();

            return $calendar->totalDaysSuspended;
        }

        return $row->petitionTerms->totalDaysOfSuspensions();
    }

    private function inAgreementWithApplicant(Petition $row): string
    {
        if ($row->isTermEngineConverted()) {
            return $row->petitionEvents
                ->where('type', PetitionEventType::MEETING_SCHEDULED)
                ->first() ? __('exports.true') : __('exports.false');
        }

        return $row->petitionTerms->hasThirdTerm() ? __('exports.true') : __('exports.false');
    }

    private function dateAgreementWithApplicant(Petition $row): ?string
    {
        if ($row->isTermEngineConverted()) {
            return $row->petitionEvents
                ->where('type', PetitionEventType::MEETING_SCHEDULED)
                ->first()?->date->toDateString() ?? null;
        }

        return $this->getThirdTermEndDate($row->petitionTerms);
    }

    private function adjourned(Petition $row): string
    {
        if ($row->isTermEngineConverted()) {
            return $row->petitionEvents
                ->where('type', PetitionEventType::ADJOURNMENT)
                ->first() ? __('exports.true') : __('exports.false');
        }

        return $row->petitionTerms->hasSecondTerm() ? __('exports.true') : __('exports.false');
    }
}
