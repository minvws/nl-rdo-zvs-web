<?php

declare(strict_types=1);

namespace App\Actions\Terms;

use App\Actions\Petition\PetitionTermsUpdateAction;
use App\Enums\TermType;
use App\Enums\TimelineType;
use App\Models\DepartmentTermTypeSetting;
use App\Models\Petition;
use App\Models\PetitionTerm;
use App\Models\User;
use App\Services\Terms\TermDateCalculator;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Support\Collection;
use Throwable;
use Webmozart\Assert\Assert;

use function array_key_exists;

readonly class PetitionTermCreateAction
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private PetitionTermsUpdateAction $petitionTermsUpdateAction,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws Throwable
     */
    public function execute(Petition $petition, TermType $termType, User $user, array $attributes): void
    {
        $penaltyAmountInEuros = $attributes['penalty_amount_in_euros'] ?? 0;
        $startDate = $this->getStartDate($attributes, $petition, $termType);
        $durationInDays = $this->getDurationInDays($attributes, $startDate);

        $petitionTerm = PetitionTerm::query()->create([
            'petition_id' => $petition->id,
            'type' => $termType,
            'start_date' => $startDate,
            'duration_in_days' => $durationInDays,
            'penalty_amount_in_euros' => $penaltyAmountInEuros,
        ]);

        $petition->petitionTerms->push($petitionTerm);

        $timelineItemCollection = new Collection();
        $timelineItemCollection->push(
            $this->makeTimelineItem($user, $termType),
        );

        if (array_key_exists('date_appealed_decision', $attributes)) {
            Assert::string($attributes['date_appealed_decision']);
            $dateAppealedDecision = CalendarDate::createFromFormat('Y-m-d', $attributes['date_appealed_decision']);

            $firstTermStartDate = $startDate->addDays($durationInDays) > $dateAppealedDecision
                ? $startDate->addDays($durationInDays)
                : $dateAppealedDecision->addDay();

            $firstTermType = TermType::FIRST;
            $departmentTermTypeSettings = DepartmentTermTypeSetting::whereDepartmentAndType($petition->department, $firstTermType)->get();
            $firstTermDurationInDays = $departmentTermTypeSettings->firstWhere('field', 'duration_in_days')?->default_value;
            Assert::string($firstTermDurationInDays);

            $petitionTerm = PetitionTerm::query()->create([
                'petition_id' => $petition->id,
                'type' => $firstTermType,
                'start_date' => $firstTermStartDate,
                'duration_in_days' => (int) $firstTermDurationInDays,
            ]);
            $petition->petitionTerms->push($petitionTerm);
        }

        if (array_key_exists('penalty_terms', $attributes)) {
            Assert::isArray($attributes['penalty_terms']);
            $penaltyTermStartDate = $petitionTerm->start_date;
            $penaltyTermDurationInDays = $durationInDays;

            foreach ($attributes['penalty_terms'] as $penaltyTermAttributes) {
                $penaltyTermStartDate = $penaltyTermStartDate->addDays($penaltyTermDurationInDays);

                Assert::isArray($penaltyTermAttributes);
                Assert::keyExists($penaltyTermAttributes, 'duration_in_days');
                if ($penaltyTermAttributes['duration_in_days'] === null) {
                    continue;
                }

                Assert::keyExists($penaltyTermAttributes, 'penalty_amount_in_euros');
                Assert::scalar($penaltyTermAttributes['duration_in_days']);

                $penaltyTermStartDate = $petitionTerm->start_date->addDays($durationInDays);
                $durationInDays = (int) $penaltyTermAttributes['duration_in_days'];
                $parentId = $petitionTerm->id;
                $penaltyTerm = PetitionTerm::query()->create([
                    'petition_id' => $petition->id,
                    'type' => TermType::PENALTY,
                    'start_date' => $penaltyTermStartDate,
                    'duration_in_days' => (int) $penaltyTermAttributes['duration_in_days'],
                    'penalty_amount_in_euros' => $penaltyTermAttributes['penalty_amount_in_euros'],
                    'parent_id' => $parentId,
                ]);

                $petition->petitionTerms->push($penaltyTerm);
                $petitionTerm = $penaltyTerm;

                $timelineItemCollection->push(
                    $this->makeTimelineItem($user, TermType::PENALTY),
                );
            }
        }

        $this->databaseManager->transaction(function () use ($petition, $timelineItemCollection): void {
            $petition->petitionTerms()->saveMany($petition->petitionTerms);
            $petition->timelineItems()->createMany($timelineItemCollection);
            $this->petitionTermsUpdateAction->execute($petition);
        });
    }

    /**
     * @param array<array-key, mixed> $attributes
     */
    private function getStartDate(array $attributes, Petition $petition, TermType $termType): CalendarDate
    {
        if (array_key_exists('start_date', $attributes)) {
            $startDate = $attributes['start_date'];
            Assert::string($startDate);

            return match ($termType) {
                TermType::APPEAL_NOT_TIMELY,
                TermType::NOTICE_OF_DEFAULT => CalendarDate::createFromFormat('Y-m-d', $startDate)->addDays(1),
                default => CalendarDate::createFromFormat('Y-m-d', $startDate),
            };
        }

        // check if predecessor term exists
        $predecessorTerm = $petition->petitionTerms->getPredecessorFromType($termType);

        if ($predecessorTerm !== null) {
            return $predecessorTerm->end_date->addDays(1);
        }

        return $petition->date_of_entry;
    }

    /**
     * @param array<array-key, mixed> $attributes
     */
    private function getDurationInDays(array $attributes, CalendarDate $startDate): int
    {
        if (array_key_exists('duration_in_days', $attributes)) {
            $durationInDays = $attributes['duration_in_days'];
            Assert::string($durationInDays);

            return (int) $durationInDays;
        }

        if (array_key_exists('end_date', $attributes)) {
            $requestedEndDate = $attributes['end_date'];
            Assert::string($requestedEndDate);

            $requestedEndDate = CalendarDate::createFromFormat('Y-m-d', $requestedEndDate);

            return TermDateCalculator::calculateDuration($startDate, $requestedEndDate);
        }

        return 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function makeTimelineItem(User $user, TermType $termType): array
    {
        return [
            'type' => TimelineType::TERM_CREATED,
            'user_id' => $user->id,
            'data' => new ArrayObject([
                'term_type' => $termType,
            ]),
        ];
    }
}
