<?php

declare(strict_types=1);

namespace App\Services\PetitionEvent;

use App\Enums\PetitionEventType;
use App\Enums\UnspecifiedAdjournmentMode;
use App\Services\DerivedState;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;

use function __;
use function sprintf;
use function trans_choice;

/**
 * What the "Termijn" column of the events table shows for every event.
 */
class PetitionEventTermService
{
    public function termText(
        WizardEventCollection $events,
        PetitionEventData $event,
        UnspecifiedAdjournmentMode $mode,
    ): string {
        if ($event->type === PetitionEventType::UNSPECIFIED_ADJOURNMENT_END) {
            return $this->appliedAdjournmentTermText($events, $event, $mode);
        }

        return $this->durationText($event);
    }

    /**
     * The end of an unspecified adjournment shows the term that it applied, which needs the calendar of all events
     * and is none when the original term runs longer.
     */
    private function appliedAdjournmentTermText(
        WizardEventCollection $events,
        PetitionEventData $event,
        UnspecifiedAdjournmentMode $mode,
    ): string {
        $appliedTerm = new DerivedState()
            ->withUnspecifiedAdjournmentMode($mode)
            ->addEvents($events->all())
            ->buildCalendar()
            ->findDay($event->date)
            ?->appliedUnspecifiedAdjournmentTerm;

        return $appliedTerm === null ? '' : $this->days($appliedTerm);
    }

    /**
     * An unspecified adjournment that registered a term for both ways it can end shows both.
     */
    private function durationText(PetitionEventData $event): string
    {
        if ($event->type === PetitionEventType::UNSPECIFIED_ADJOURNMENT && $event->withdrawalDuration !== null) {
            return __('term.event_and_withdrawal_days', [
                'event' => $event->duration ?? 0,
                'withdrawal' => $event->withdrawalDuration,
            ]);
        }

        $duration = $event->duration ?? 0;

        return $duration > 0 ? $this->days($duration) : '';
    }

    private function days(int $days): string
    {
        return sprintf('%d %s', $days, trans_choice('general.days', $days));
    }
}
