<?php

declare(strict_types=1);

namespace App\Actions\PetitionEvent;

use App\Actions\PetitionEvent\Contracts\UpdatePetitionTotalsFromEventsActionInterface;
use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Factories\WizardEventCollectionFactory;
use App\Models\Petition;
use App\Services\DerivedState;
use App\ValueObjects\CalendarDate;

class UpdatePetitionTotalsFromEventsAction implements UpdatePetitionTotalsFromEventsActionInterface
{
    public function __construct(
        private readonly DerivedState $derivedState,
        private readonly WizardEventCollectionFactory $factory,
    ) {
    }

    public function execute(Petition $petition): void
    {
        if (!$petition->isTermEngineConverted()) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        $events = $this->factory::fromModels($petition->petitionEvents)->all();
        $this->derivedState->addEvents($events)->buildCalendar();

        $dateOfEntry = $this->derivedState->dateOfEntry() ?? $petition->date_of_entry;
        $calendar = $this->derivedState->getCalendar();
        $deadline = $this->derivedState->deadlineDate();
        $today = CalendarDate::today();

        $updates = [
            'deadline_at' => $deadline,
            'total_days_suspended' => $calendar->totalDaysSuspended,

            'igs_penalty_today' => $this->derivedState->penaltyTodayForTerm($today, TermType::NOTICE_OF_DEFAULT),
            'igs_forfeited' => $this->derivedState->forfeitedForTerm($today, TermType::NOTICE_OF_DEFAULT),
            'igs_penalty_maximum' => $this->derivedState->maximumPenaltyForEventType(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED),

            'bnt_penalty_today' => $this->derivedState->penaltyTodayForTerm($today, TermType::APPEAL_NOT_TIMELY),
            'bnt_forfeited' => $this->derivedState->forfeitedForTerm($today, TermType::APPEAL_NOT_TIMELY),
            'bnt_penalty_maximum' => $this->derivedState->maximumPenaltyForEventType(PetitionEventType::APPEAL_DECISION_NOT_TIMELY),

            'legacy_term_penalty_today' => 0,
            'legacy_term_forfeited' => 0,
            'legacy_term_penalty_maximum' => 0,
        ];

        if (!$dateOfEntry->equals($petition->date_of_entry)) {
            $updates['date_of_entry'] = $dateOfEntry;
        }

        $petition->update($updates);
    }
}
