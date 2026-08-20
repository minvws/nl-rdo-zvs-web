<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Enums\TermType;
use App\Models\Department;
use App\Models\Petition;
use App\Services\Petition\PetitionParticularityCollector;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

use function array_map;

/**
 * @method static PetitionQueryBuilder query()
 *
 * @template-extends Builder<Petition>
 */
class PetitionQueryBuilder extends Builder
{
    public function notArchived(): PetitionQueryBuilder
    {
        return $this->whereNull('archived_at');
    }

    public function whereDepartment(Department $department): PetitionQueryBuilder
    {
        return $this->where('department_id', $department->id);
    }

    public function withSumOfPenaltiesPerDate(): PetitionQueryBuilder
    {
        return $this->addSelect([
            'petitions.*',
            DB::raw('legacy_term_penalty_today + igs_penalty_today + bnt_penalty_today as sum_of_penalties_per_date'),
        ]);
    }

    public function withPenaltyToDate(): PetitionQueryBuilder
    {
        return $this->addSelect([
            'petitions.*',
            DB::raw('legacy_term_forfeited + igs_forfeited + bnt_forfeited as penalty_to_date'),
        ]);
    }

    public function withParticularitySortKey(): PetitionQueryBuilder
    {
        return $this->addSelect('petitions.*')->selectSub(
            $this->particularitySortKeyQuery(),
            'particularity_sort_key',
        );
    }

    public function whereParticularity(string $particularity): PetitionQueryBuilder
    {
        return $this->whereExists(
            DB::query()
                ->fromSub($this->particularityLabelsQuery(), 'particularities')
                ->selectRaw('1')
                ->where('label', $particularity),
        );
    }

    private function particularitySortKeyQuery(): QueryBuilder
    {
        return DB::query()
            ->fromSub($this->particularityLabelsQuery(), 'particularities')
            ->selectRaw("array_to_string(array_agg(DISTINCT label ORDER BY label), chr(31))");
    }

    private function particularityLabelsQuery(): QueryBuilder
    {
        $today = CalendarDate::today()->toDateString();

        return $this->relatedPetitionParticularityLabelsQuery()
            ->unionAll($this->noticeOfDefaultParticularityLabelQuery())
            ->unionAll($this->suspensionParticularityLabelQuery($today))
            ->unionAll($this->adjournmentParticularityLabelQuery($today))
            ->unionAll($this->appealNotTimelyParticularityLabelQuery($today))
            ->unionAll($this->adjournmentLetterParticularityLabelQuery());
    }

    private function relatedPetitionParticularityLabelsQuery(): QueryBuilder
    {
        return DB::query()
            ->from('petition_petition')
            ->join('petitions as related_petitions', 'related_petitions.id', '=', 'petition_petition.related_petition_id')
            ->join('petition_types', 'petition_types.id', '=', 'related_petitions.petition_type_id')
            ->selectRaw('petition_types.particularity_label as label')
            ->whereColumn('petition_petition.petition_id', 'petitions.id')
            ->whereNotNull('petition_types.particularity_label');
    }

    private function noticeOfDefaultParticularityLabelQuery(): QueryBuilder
    {
        return DB::query()
            ->selectRaw('? as label', [PetitionParticularityCollector::LABEL_NOTICE_OF_DEFAULT])
            ->where(static function (QueryBuilder $query): void {
                $query->whereExists(self::noticeOfDefaultEventQuery())
                    ->orWhere(static function (QueryBuilder $query): void {
                        $query->whereNotExists(self::anyPetitionEventQuery())
                            ->whereExists(self::noticeOfDefaultTermQuery());
                    });
            });
    }

    private static function anyPetitionEventQuery(): QueryBuilder
    {
        return DB::query()
            ->from('petition_events')
            ->selectRaw('1')
            ->whereColumn('petition_events.petition_id', 'petitions.id');
    }

    private static function noticeOfDefaultTermQuery(): QueryBuilder
    {
        return DB::query()
            ->from('petition_terms')
            ->selectRaw('1')
            ->whereColumn('petition_terms.petition_id', 'petitions.id')
            ->where('type', TermType::NOTICE_OF_DEFAULT->value);
    }

    private static function noticeOfDefaultEventQuery(): QueryBuilder
    {
        return DB::query()
            ->from('petition_events')
            ->selectRaw('1')
            ->whereColumn('petition_events.petition_id', 'petitions.id')
            ->groupBy('petition_events.petition_id')
            ->havingRaw(
                'count(*) filter (where type = ?) > count(*) filter (where type = ?)',
                [
                    PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->value,
                    PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN->value,
                ],
            );
    }

    private function suspensionParticularityLabelQuery(string $today): QueryBuilder
    {
        return DB::query()
            ->selectRaw('? as label', [PetitionParticularityCollector::LABEL_SUSPENSION])
            ->where(static function (QueryBuilder $query) use ($today): void {
                $query->whereExists(
                    self::activeSuspensionLetterEventQuery(PetitionParticularityCollector::SUSPENSION_TYPES, $today),
                )
                    ->orWhere(static function (QueryBuilder $query) use ($today): void {
                        $query->whereNotExists(self::anyPetitionEventQuery())
                            ->whereExists(self::suspensionTermQuery($today));
                    });
            });
    }

    /**
     * A suspension runs from the day after the letter was sent until either the day before the
     * registered end, or the last day of the duration stated in the letter.
     *
     * @param array<SuspensionType> $suspensionTypes
     */
    private static function activeSuspensionLetterEventQuery(array $suspensionTypes, string $today): QueryBuilder
    {
        return DB::query()
            ->from('petition_events as suspension_start')
            ->selectRaw('1')
            ->whereColumn('suspension_start.petition_id', 'petitions.id')
            ->where('suspension_start.type', PetitionEventType::LETTER_OF_SUSPENSION_SENT->value)
            ->whereIn(
                'suspension_start.suspension_type',
                array_map(static fn(SuspensionType $type): string => $type->value, $suspensionTypes),
            )
            ->whereRaw('suspension_start."date" + 1 <= ?::date', [$today])
            ->whereRaw(
                <<<'SQL'
                ?::date <= coalesce(
                    (
                        select suspension_end."date" - 1
                        from petition_events as suspension_end
                        where suspension_end.petition_id = suspension_start.petition_id
                          and suspension_end.type = ?
                          and suspension_end."date" >= suspension_start."date" + 1
                        order by suspension_end."date"
                        limit 1
                    ),
                    suspension_start."date" + coalesce(suspension_start.duration, 0)
                )
                SQL,
                [$today, PetitionEventType::SUSPENSION_END->value],
            );
    }

    private static function suspensionTermQuery(string $today): QueryBuilder
    {
        return DB::query()
            ->from('petition_terms')
            ->selectRaw('1')
            ->whereColumn('petition_terms.petition_id', 'petitions.id')
            ->where('type', TermType::SUSPENSION->value)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }

    private function adjournmentParticularityLabelQuery(string $today): QueryBuilder
    {
        return DB::query()
            ->selectRaw('? as label', [PetitionParticularityCollector::LABEL_ADJOURNMENT])
            ->where(static function (QueryBuilder $query) use ($today): void {
                $query->whereExists(
                    self::activeSuspensionLetterEventQuery([SuspensionType::SPECIFIED_ADJOURNMENT], $today),
                )
                    ->orWhere(static function (QueryBuilder $query): void {
                        $query->whereExists(
                            self::petitionEventOfTypeQuery(PetitionEventType::UNSPECIFIED_ADJOURNMENT),
                        )->whereNotExists(
                            self::petitionEventOfTypeQuery(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END),
                        );
                    })
                    ->orWhere(static function (QueryBuilder $query) use ($today): void {
                        $query->whereNotExists(self::anyPetitionEventQuery())
                            ->whereExists(self::adjournmentTermQuery($today));
                    })
                    ->orWhereExists(self::adjournmentDraftTermQuery($today));
            });
    }

    private function appealNotTimelyParticularityLabelQuery(string $today): QueryBuilder
    {
        return DB::query()
            ->selectRaw('? as label', [PetitionParticularityCollector::LABEL_APPEAL_NOT_TIMELY])
            ->whereExists($this->runningAppealNotTimelyTermQuery($today));
    }

    private function adjournmentLetterParticularityLabelQuery(): QueryBuilder
    {
        return DB::query()
            ->selectRaw('? as label', [PetitionParticularityCollector::LABEL_ADJOURNMENT_LETTER])
            ->whereExists(self::petitionEventOfTypeQuery(PetitionEventType::ADJOURNMENT));
    }

    private function runningAppealNotTimelyTermQuery(string $today): QueryBuilder
    {
        return self::petitionEventOfTypeQuery(PetitionEventType::APPEAL_DECISION_NOT_TIMELY)
            ->where('petition_events.duration', '>', 0)
            ->whereRaw('petition_events."date" <= ?::date', [$today])
            ->whereRaw('?::date <= petition_events."date" + petition_events.duration', [$today]);
    }

    private static function petitionEventOfTypeQuery(PetitionEventType $type): QueryBuilder
    {
        return self::anyPetitionEventQuery()->where('petition_events.type', $type->value);
    }

    private static function adjournmentTermQuery(string $today): QueryBuilder
    {
        return DB::query()
            ->from('petition_terms')
            ->selectRaw('1')
            ->whereColumn('petition_terms.petition_id', 'petitions.id')
            ->whereIn('type', [
                TermType::SPECIFIED_ADJOURNMENT->value,
                TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT->value,
                TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL->value,
            ])
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }

    private static function adjournmentDraftTermQuery(string $today): QueryBuilder
    {
        return DB::query()
            ->from('petition_draft_terms')
            ->selectRaw('1')
            ->whereColumn('petition_draft_terms.petition_id', 'petitions.id')
            ->whereDate('start_date', '>', $today);
    }
}
