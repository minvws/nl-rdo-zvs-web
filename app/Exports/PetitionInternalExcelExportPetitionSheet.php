<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\ResultType;
use App\Models\Petition;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Override;

use function __;

class PetitionInternalExcelExportPetitionSheet extends PetitionAbstractExcelExport implements WithColumnFormatting
{
    public function __construct(ExportCriteria $criteria)
    {
        parent::__construct('petition_sheet', $criteria);
    }

    /**
     * @return Builder<Petition>
     */
    #[Override]
    public function query(): Builder
    {
        return parent::query()->with(['team']);
    }

    /**
     * @param Petition $row
     *
     * @return array<array-key, mixed>
     */
    public function map(mixed $row): array
    {
        $termEngineConverted = $row->isTermEngineConverted();

        return [
            $row->number,
            $row->name ?? '-',
            $row->applicant->first()?->display_name,
            $row->firstAssignee?->user->name ?? __('petition.not_assigned'),
            $row->policyDepartments->toString(),
            $row->petitionType->name,
            $row->petitionCategory?->name,
            $row->team->name ?? '-',
            ExcelDate::fromCalendarDate($row->date_of_entry),
            ExcelDate::fromCalendarDate($row->date_appealed_decision),
            ExcelDate::fromCalendarDate($row->effective_deadline_at),
            ExcelDate::fromDateTime($row->created_at),
            $row->legacy_term_penalty_today + $row->igs_penalty_today + $row->bnt_penalty_today,
            $row->legacy_term_forfeited + $row->igs_forfeited + $row->bnt_forfeited,
            $row->relatedPetitions->toString(),
            $this->dateWithdrawn($row, $termEngineConverted),
            $this->dateDecisionOnAppeal($row, $termEngineConverted),
            $row->daysPending,
            __('petition_status.' . $row->petitionStatus->status_group->value),
            $row->petitionStatus->status,
            $this->formatMatchingCustomOptions($row->customPetitionProperties, [
                'Binnen wettelijke termijn' => 'Binnen wettelijke termijn',
                'Binnen afgesproken termijn' => 'Binnen afgesproken termijn',
                'Buiten wettelijke/afgesproken termijn' => 'Buiten wettelijke/afgesproken termijn',
            ]),
            $this->forwarding($row, $termEngineConverted),
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
            $this->dateSettlementWithoutDecision($row, $termEngineConverted),
            $this->dateOfLatestDecision($row),
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
            __('team.model_singular'),
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
            __('petition.substatus'),
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
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'I' => ExcelDate::DATE_FORMAT,
            'J' => ExcelDate::DATE_FORMAT,
            'K' => ExcelDate::DATE_FORMAT,
            'L' => ExcelDate::DATETIME_FORMAT,
            'P' => ExcelDate::DATE_FORMAT,
            'Q' => ExcelDate::DATE_FORMAT,
            'Z' => ExcelDate::DATE_FORMAT,
            'AA' => ExcelDate::DATE_FORMAT,
        ];
    }

    private function forwarding(Petition $row, bool $termEngineConverted): string
    {
        if ($termEngineConverted) {
            return $row->petitionEvents->finalResult(ResultType::FORWARDED) !== null ? 'Doorzending' : '';
        }

        return $this->formatMatchingCustomOptions($row->customPetitionProperties, ['Doorzending' => 'Doorzending']);
    }
}
