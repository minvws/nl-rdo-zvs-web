<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\CustomDateLabel;
use App\Facades\DisplayDate;
use App\Models\Decision;
use App\Models\Petition;
use App\ValueObjects\CalendarDate;
use Illuminate\Support\Collection;

use function __;

class PetitionInternalExcelExportPetitionSheet extends PetitionAbstractExcelExport
{
    public function __construct(ExportCriteria $criteria)
    {
        parent::__construct('petition_sheet', $criteria);
    }

    /**
     * @param Petition $row
     *
     * @return array<array-key, mixed>
     */
    public function map(mixed $row): array
    {
        return [
            $row->number,
            $row->name ?? '-',
            $row->applicant->first()?->display_name,
            $row->assignedUser ? $row->assignedUser->name : __('petition.not_assigned'),
            $row->policyDepartments->toString(),
            $row->petitionType->name,
            $row->petitionCategory?->name,
            DisplayDate::date($row->date_of_entry),
            $row->date_appealed_decision ? DisplayDate::date($row->date_appealed_decision) : '-',
            $row->deadline_at ? DisplayDate::date($row->deadline_at) : '-',
            DisplayDate::datetime($row->created_at),
            $row->petitionTerms->penaltyToDate(CalendarDate::today()),
            $row->petitionTerms->totalPenalty(),
            $row->relatedPetitions->toString(),
            $this->formatCustomDateValueByLabel($row->customDates, CustomDateLabel::DATE_WITHDRAWN),
            $this->formatCustomDateValueByLabel($row->customDates, CustomDateLabel::DATE_DECISION_ON_APPEAL),
            $row->daysPending,
            __('petition_status.' . $row->petitionStatus->status_group->value),
            $this->formatMatchingCustomOptions($row->customPetitionProperties, [
                'Binnen wettelijke termijn' => 'Binnen wettelijke termijn',
                'Binnen afgesproken termijn' => 'Binnen afgesproken termijn',
                'Buiten wettelijke/afgesproken termijn' => 'Buiten wettelijke/afgesproken termijn',
            ]),
            $this->formatMatchingCustomOptions($row->customPetitionProperties, ['Doorzending' => 'Doorzending']),
            $this->formatMatchingCustomOptions($row->customPetitionProperties, [
                'Herziening – herstel bezwaar' => 'Herziening - herstel bezwaar', // watch it: dashes are different
                'Herziening – herstel primair besluit' => 'Herziening - herstel primair besluit', // watch it: dashes are different
                'Informeel' => 'Informeel',
                'Overig' => 'Overig',
            ]),
            $this->formatMatchingCustomOptions($row->customPetitionProperties, [
                'A' => 'A',
                'B' => 'B',
                'C' => 'C',
                'D' => 'D',
                'E' => 'E',
            ]),
            $this->formatMatchingCustomOptions($row->customPetitionProperties, [
                'Gegrond' => 'Gegrond',
                'Ongegrond' => 'Ongegrond',
                'Intrekking' => 'Intrekking',
                'Niet-ontvankelijk' => 'Niet-ontvankelijk',
                'Kennelijk niet-ontvankelijk' => 'Kennelijk niet-ontvankelijk',
                'Kennelijk gegrond' => 'Kennelijk gegrond',
                'Kennelijk ongegrond' => 'Kennelijk ongegrond',
            ]),
            $this->formatCustomDateValueByLabel($row->customDates, CustomDateLabel::DATE_SETTLEMENT_WITHOUT_DECISION),
            $this->getDateOfDecision($row->decisions),
        ];
    }

    /**
     * @return array<string>
     */
    public function headings(): array
    {
        return [
            __('petition.number'),
            __('petition.name'),
            __('requesting_party.model_singular'),
            __('petition.assigned_user'),
            __('policy_department.model_singular'),
            __('petition.type'),
            __('petition.category'),
            __('petition.date_of_entry.bezwaar'),
            __('petition.date_appealed_decision.bezwaar'),
            __('petition.deadline_at'),
            __('general.date_and_timestamp'),
            __('term.sum_of_penalties_per_date'),
            __('term.penalty_to_date'),
            __('petition.attached_petitions'),
            __('exports.date_withdrawn'),
            __('exports.date_decision'),
            __('petition.days_in_progress'),
            __('petition_status.status'),
            __('exports.within_outside_term'),
            __('exports.forwarding'),
            __('exports.withdrawal'),
            __('exports.weight'),
            __('exports.dictum'),
            __('exports.date_settlement_without_decision'),
            __('exports.date_decision'),
        ];
    }

    /**
     * @param Collection<int, Decision> $decisions
     */
    private function getDateOfDecision(Collection $decisions): ?string
    {
        $latestDecision = $decisions->sortByDesc('date')->first();

        return $latestDecision?->date?->format('Y-m-d');
    }
}
