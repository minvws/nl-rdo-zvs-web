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
use Illuminate\Support\Collection;
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

    /**
     * Returns the particularity labels that occur on at least one petition matched by this query. It is built from
     * the same label queries as the particularity filter, so every returned label yields at least one petition when
     * filtered on.
     *
     * Each label query is joined laterally on the petitions separately, so the database only evaluates the conditions
     * of one label at a time. A calculated label query only produces its own label, so it can stop at the first match.
     *
     * @return Collection<int, string>
     */
    public function pluckOccurringParticularities(): Collection
    {
        $petitions = $this->toBase()
            ->cloneWithout(['columns', 'orders', 'limit', 'offset'])
            ->cloneWithoutBindings(['select', 'order']);

        $query = $this->occurringLabelsQuery($petitions, $this->relatedPetitionParticularityLabelsQuery(
            'petition_id',
            'related_petition_id',
        ))->union($this->occurringLabelsQuery($petitions, $this->relatedPetitionParticularityLabelsQuery(
            'related_petition_id',
            'petition_id',
        )));

        foreach ($this->calculatedParticularityLabelQueries(CalendarDate::today()) as $labelQuery) {
            $query->union($this->occurringLabelsQuery($petitions, $labelQuery)->limit(1));
        }

        /** @var Collection<int, string> $labels */
        $labels = $query->pluck('label');

        return $labels->sort()->values();
    }

    private function occurringLabelsQuery(QueryBuilder $petitions, QueryBuilder $labels): QueryBuilder
    {
        return DB::query()->fromSub(
            $petitions->clone()
                ->joinLateral($labels, 'particularities')
                ->select('particularities.label'),
            'occurring_particularities',
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
        $query = $this->relatedPetitionParticularityLabelsQuery('petition_id', 'related_petition_id')
            ->unionAll($this->relatedPetitionParticularityLabelsQuery('related_petition_id', 'petition_id'));

        foreach ($this->calculatedParticularityLabelQueries(CalendarDate::today()) as $labelQuery) {
            $query->unionAll($labelQuery);
        }

        return $query;
    }

    /**
     * @return array<QueryBuilder>
     */
    private function calculatedParticularityLabelQueries(CalendarDate $today): array
    {
        return [
            $this->noticeOfDefaultParticularityLabelQuery(),
            $this->suspensionParticularityLabelQuery($today),
            $this->adjournmentParticularityLabelQuery($today),
            $this->appealNotTimelyParticularityLabelQuery($today),
            $this->receiptAppealNotTimelyParticularityLabelQuery(),
            $this->adjournmentLetterParticularityLabelQuery(),
        ];
    }

    private function relatedPetitionParticularityLabelsQuery(
        string $petitionKey,
        string $relatedPetitionKey,
    ): QueryBuilder {
        return DB::query()
            ->from('petition_petition')
            ->join('petitions as related_petitions', 'related_petitions.id', '=', 'petition_petition.' . $relatedPetitionKey)
            ->join('petition_types', 'petition_types.id', '=', 'related_petitions.petition_type_id')
            ->selectRaw('petition_types.particularity_label as label')
            ->whereColumn('petition_petition.' . $petitionKey, 'petitions.id')
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

    private function suspensionParticularityLabelQuery(CalendarDate $today): QueryBuilder
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
    private static function activeSuspensionLetterEventQuery(array $suspensionTypes, CalendarDate $today): QueryBuilder
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
            ->whereRaw('suspension_start."date" + 1 <= ?::date', [$today->toDateString()])
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
                [$today->toDateString(), PetitionEventType::SUSPENSION_END->value],
            );
    }

    private static function suspensionTermQuery(CalendarDate $today): QueryBuilder
    {
        return DB::query()
            ->from('petition_terms')
            ->selectRaw('1')
            ->whereColumn('petition_terms.petition_id', 'petitions.id')
            ->where('type', TermType::SUSPENSION->value)
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString());
    }

    private function adjournmentParticularityLabelQuery(CalendarDate $today): QueryBuilder
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

    private function appealNotTimelyParticularityLabelQuery(CalendarDate $today): QueryBuilder
    {
        return DB::query()
            ->selectRaw('? as label', [PetitionParticularityCollector::LABEL_APPEAL_NOT_TIMELY])
            ->whereExists($this->runningAppealNotTimelyTermQuery($today));
    }

    private function receiptAppealNotTimelyParticularityLabelQuery(): QueryBuilder
    {
        return DB::query()
            ->selectRaw('? as label', [PetitionParticularityCollector::LABEL_RECEIPT_APPEAL_NOT_TIMELY])
            ->whereExists(self::petitionEventOfTypeQuery(PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY));
    }

    private function adjournmentLetterParticularityLabelQuery(): QueryBuilder
    {
        return DB::query()
            ->selectRaw('? as label', [PetitionParticularityCollector::LABEL_ADJOURNMENT_LETTER])
            ->whereExists(self::petitionEventOfTypeQuery(PetitionEventType::ADJOURNMENT));
    }

    private function runningAppealNotTimelyTermQuery(CalendarDate $today): QueryBuilder
    {
        return self::petitionEventOfTypeQuery(PetitionEventType::APPEAL_DECISION_NOT_TIMELY)
            ->where('petition_events.duration', '>', 0)
            ->whereRaw('petition_events."date" <= ?::date', [$today->toDateString()])
            ->whereRaw('?::date <= petition_events."date" + petition_events.duration', [$today->toDateString()]);
    }

    private static function petitionEventOfTypeQuery(PetitionEventType $type): QueryBuilder
    {
        return self::anyPetitionEventQuery()->where('petition_events.type', $type->value);
    }

    private static function adjournmentTermQuery(CalendarDate $today): QueryBuilder
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
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString());
    }

    private static function adjournmentDraftTermQuery(CalendarDate $today): QueryBuilder
    {
        return DB::query()
            ->from('petition_draft_terms')
            ->selectRaw('1')
            ->whereColumn('petition_draft_terms.petition_id', 'petitions.id')
            ->whereDate('start_date', '>', $today->toDateString());
    }
}
