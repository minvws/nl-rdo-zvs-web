<?php

declare(strict_types=1);

namespace App\Services\Terms;

use App\Actions\Petition\PetitionTermsUpdateAction;
use App\Enums\TermType;
use App\Enums\TimelineType;
use App\Models\Petition;
use App\Models\PetitionDraftTerm;
use App\Models\PetitionTerm;
use App\Models\User;
use App\Services\LegalTermDeadlineCalculator;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\DatabaseManager;
use Ramsey\Uuid\UuidInterface;
use Webmozart\Assert\Assert;

readonly class DraftTermToPetitionTermsService
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private LegalTermDeadlineCalculator $deadlineCalculator,
        private PetitionTermsUpdateAction $petitionTermsUpdateAction,
    ) {
    }

    public function convertDraftTermToPetitionTerms(PetitionDraftTerm $draftTerm, User $user): void
    {
        if ($draftTerm->event_date === null && $draftTerm->date_withdrawal === null) {
            return;
        }

        $petition = $draftTerm->petition;

        $this->databaseManager->transaction(function () use ($petition, $draftTerm, $user): void {
            $this->shouldUseEventDate($draftTerm)
            ? $this->createTermsForEventDate($petition, $draftTerm, $user)
            : $this->createTermsForWithdrawalDate($petition, $draftTerm, $user);

            $draftTerm->delete();

            $this->petitionTermsUpdateAction->execute($petition);
        });
    }

    private function shouldUseEventDate(PetitionDraftTerm $draftTerm): bool
    {
        if ($draftTerm->event_date === null) {
            return false;
        }

        if ($draftTerm->date_withdrawal === null) {
            return true;
        }

        return $draftTerm->event_date <= $draftTerm->date_withdrawal;
    }

    private function createTermsForEventDate(Petition $petition, PetitionDraftTerm $draftTerm, User $user): void
    {
        Assert::isInstanceOf($draftTerm->event_date, CalendarDate::class);

        $baseDuration = TermDateCalculator::calculateDuration($draftTerm->start_date, $draftTerm->event_date);

        if ($draftTerm->days_after_event <= 0) {
            $endDate = TermDateCalculator::calculateEndDate($draftTerm->start_date, $baseDuration);
            $adjustedEndDate = $this->deadlineCalculator->calculate($endDate);
            $adjustedDuration = TermDateCalculator::calculateDuration($draftTerm->start_date, $adjustedEndDate);

            $this->createTermWithTimeline(
                $petition,
                $user,
                TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT,
                $draftTerm->start_date,
                $adjustedDuration,
                null,
                $draftTerm->description,
            );

            return;
        }

        $firstTerm = $this->createTermWithTimeline(
            $petition,
            $user,
            TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT,
            $draftTerm->start_date,
            $baseDuration,
            null,
            $draftTerm->description,
        );

        $secondTermStartDate = $draftTerm->event_date->addDay();
        $secondTermEndDate = TermDateCalculator::calculateEndDate($secondTermStartDate, $draftTerm->days_after_event);
        $adjustedSecondTermEndDate = $this->deadlineCalculator->calculate($secondTermEndDate);
        $adjustedSecondTermDuration = TermDateCalculator::calculateDuration($secondTermStartDate, $adjustedSecondTermEndDate);

        $this->createTermWithTimeline(
            $petition,
            $user,
            TermType::PENDING_TERM_AFTER_EVENT,
            $secondTermStartDate,
            $adjustedSecondTermDuration,
            $firstTerm->id,
        );
    }

    private function createTermsForWithdrawalDate(Petition $petition, PetitionDraftTerm $draftTerm, User $user): void
    {
        Assert::isInstanceOf($draftTerm->date_withdrawal, CalendarDate::class);

        $baseDuration = TermDateCalculator::calculateDuration($draftTerm->start_date, $draftTerm->date_withdrawal);

        if ($draftTerm->days_after_date_withdrawal === null || $draftTerm->days_after_date_withdrawal <= 0) {
            $endDate = TermDateCalculator::calculateEndDate($draftTerm->start_date, $baseDuration);
            $adjustedEndDate = $this->deadlineCalculator->calculate($endDate);
            $adjustedDuration = TermDateCalculator::calculateDuration($draftTerm->start_date, $adjustedEndDate);

            $this->createTermWithTimeline(
                $petition,
                $user,
                TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL,
                $draftTerm->start_date,
                $adjustedDuration,
                null,
                $draftTerm->description,
            );

            return;
        }

        $firstTerm = $this->createTermWithTimeline(
            $petition,
            $user,
            TermType::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL,
            $draftTerm->start_date,
            $baseDuration,
            null,
            $draftTerm->description,
        );

        $secondTermStartDate = $draftTerm->date_withdrawal->addDay();
        $secondTermEndDate = TermDateCalculator::calculateEndDate($secondTermStartDate, $draftTerm->days_after_date_withdrawal);
        $adjustedSecondTermEndDate = $this->deadlineCalculator->calculate($secondTermEndDate);
        $adjustedSecondTermDuration = TermDateCalculator::calculateDuration($secondTermStartDate, $adjustedSecondTermEndDate);

        $this->createTermWithTimeline(
            $petition,
            $user,
            TermType::PENDING_TERM_AFTER_WITHDRAWAL,
            $secondTermStartDate,
            $adjustedSecondTermDuration,
            $firstTerm->id,
        );
    }

    private function createTermWithTimeline(
        Petition $petition,
        User $user,
        TermType $type,
        CalendarDate $startDate,
        int $durationInDays,
        ?UuidInterface $parentId,
        ?string $description = null,
    ): PetitionTerm {
        $petitionTerm = $this->createPetitionTerm($petition, $type, $startDate, $durationInDays, $parentId, $description);

        $this->createTimelineEntry($petition, $user, $type);

        return $petitionTerm;
    }

    private function createTimelineEntry(Petition $petition, User $user, TermType $termType): void
    {
        $petition->timelineItems()->create([
            'user_id' => $user->id,
            'type' => TimelineType::TERM_CREATED,
            'data' => [
                'term_type' => $termType,
            ],
        ]);
    }

    private function createPetitionTerm(
        Petition $petition,
        TermType $type,
        CalendarDate $startDate,
        int $durationInDays,
        ?UuidInterface $parentId,
        ?string $description = null,
    ): PetitionTerm {
        return PetitionTerm::query()->create([

            'description' => $description,
            'petition_id' => $petition->id,
            'type' => $type,
            'start_date' => $startDate,
            'duration_in_days' => $durationInDays,

            'parent_id' => $parentId,
        ]);
    }
}
