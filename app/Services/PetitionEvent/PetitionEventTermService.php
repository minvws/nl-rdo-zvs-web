<?php

declare(strict_types=1);

namespace App\Services\PetitionEvent;

use App\Enums\PetitionEventType;
use App\Enums\UnspecifiedAdjournmentMode;
use App\Services\DerivedState;
use App\ValueObjects\EventCalendar;
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
    /**
     * @return array<int, string> keyed like the events
     */
    public function termTexts(WizardEventCollection $events, UnspecifiedAdjournmentMode $mode): array
    {
        $calendar = $this->calendar($events, $mode);

        return $events->all()
            ->map(fn(PetitionEventData $event): string => $this->termText($event, $calendar))
            ->all();
    }

    /**
     * Only the end of an unspecified adjournment needs the calendar, so it is not built without one.
     */
    private function calendar(WizardEventCollection $events, UnspecifiedAdjournmentMode $mode): ?EventCalendar
    {
        $hasAdjournmentEnd = $events->all()->contains(
            static fn(PetitionEventData $event): bool => $event->type === PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
        );

        if (!$hasAdjournmentEnd) {
            return null;
        }

        return new DerivedState()
            ->withUnspecifiedAdjournmentMode($mode)
            ->addEvents($events->all())
            ->buildCalendar()
            ->getCalendar();
    }

    /**
     * An unspecified adjournment that registered a term for both ways it can end shows both. Its
     * end shows the term that it applied, which is none when the original term runs longer.
     */
    private function termText(PetitionEventData $event, ?EventCalendar $calendar): string
    {
        if ($event->type === PetitionEventType::UNSPECIFIED_ADJOURNMENT_END) {
            $appliedTerm = $calendar?->findDay($event->date)?->appliedUnspecifiedAdjournmentTerm;

            return $appliedTerm === null ? '' : $this->days($appliedTerm);
        }

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
