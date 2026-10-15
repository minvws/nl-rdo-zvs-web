<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Config\DepartmentConfigurationService;
use App\Enums\AdjournmentEndReason;
use App\Enums\DecisionPeriodType;
use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Enums\TermType;
use App\Models\Department;
use App\Services\DerivedState;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function __;
use function array_filter;
use function array_values;
use function implode;
use function sprintf;
use function view;

class ObjectionEventsCalendar extends Component
{
    private readonly EventCalendar $calendar;

    public function __construct(
        private readonly WizardEventCollection $events,
        private readonly Department $department,
        DepartmentConfigurationService $departmentConfiguration,
    ) {
        $state = new DerivedState();
        $state->withUnspecifiedAdjournmentMode($departmentConfiguration->unspecifiedAdjournmentMode($this->department));
        $state->addEvents($this->events->all());
        $state->buildCalendar();
        $this->calendar = $state->getCalendar();
    }

    /**
     * A suspension wins over the type of decision period, so a suspended day always reads as
     * suspended. Any decision period that is not the statutory one names its type.
     */
    public function termLabel(EventCalendarDay $day): string
    {
        if ($day->applicableTerm === null) {
            return '';
        }

        if ($day->suspensionType instanceof SuspensionType) {
            return __(sprintf('term.%s.%s', $day->applicableTerm, $day->suspensionType->value));
        }

        $decisionPeriodType = $this->nonStatutoryDecisionPeriodType($day);

        if ($decisionPeriodType instanceof DecisionPeriodType) {
            return __(sprintf('term.decision_period_type.%s', $decisionPeriodType->value));
        }

        return __(sprintf('term.%s.default', $day->applicableTerm));
    }

    /**
     * What the "Termijn" column shows: the term, plus the penalty of that day if there is one.
     */
    public function termText(EventCalendarDay $day): string
    {
        if ($day->penaltyTodayInEuros === 0) {
            return $this->termLabel($day);
        }

        return sprintf('%s: €%d', $this->termLabel($day), $day->penaltyTodayInEuros);
    }

    /**
     * A day with an event or a "bijzonderheid" always stays visible when the calendar is folded.
     */
    public function hasEventsOrParticularities(EventCalendarDay $day): bool
    {
        return $day->petitionEvents !== []
            || $day->isATW
            || $day->isDeadline
            || $day->isOriginalDeadline
            || $day->isOpenUnspecifiedAdjournment;
    }

    /**
     * The label an event gets in the "Gebeurtenis" column. Ending an unspecified adjournment by
     * withdrawal has a label of its own; the other events name their suspension or result type.
     */
    public function eventLabel(PetitionEventData $event): string
    {
        if ($this->isUnspecifiedAdjournmentWithdrawal($event)) {
            return __('term.unspecified_adjournment_end_withdrawal');
        }

        return __(implode('.', ['term', $event->type->value, ...$this->eventVariants($event)]));
    }

    /**
     * Every label has its own colour class: one for the type of event and, when the label names
     * more than the type, one for that variant. A variant without a colour of its own shows the
     * colour of its type.
     */
    public function eventClass(PetitionEventData $event): string
    {
        $variant = $this->isUnspecifiedAdjournmentWithdrawal($event)
            ? 'withdrawal'
            : implode('-', $this->eventVariants($event));

        return 'event-type ' . $this->colourClasses('event', $event->type->value, $variant);
    }

    /**
     * Follows the same order as the label: a suspension wins over the type of decision period.
     */
    public function termClass(EventCalendarDay $day): string
    {
        if ($day->applicableTerm === null) {
            return 'term-type';
        }

        return 'term-type ' . $this->colourClasses('term', $day->applicableTerm, $this->termVariant($day));
    }

    public function render(): View|Closure|string
    {
        return view('components.objection-events-calendar', [
            'calendarItems' => $this->calendar,
        ]);
    }

    private function termVariant(EventCalendarDay $day): string
    {
        if ($day->suspensionType instanceof SuspensionType) {
            return $day->suspensionType->value;
        }

        return $this->nonStatutoryDecisionPeriodType($day)->value ?? '';
    }

    private function colourClasses(string $group, string $name, string $variant): string
    {
        $colourClass = sprintf('calendar-colour--%s-%s', $group, $name);

        if ($variant === '') {
            return $colourClass;
        }

        return sprintf('%s %s-%s', $colourClass, $colourClass, $variant);
    }

    private function isUnspecifiedAdjournmentWithdrawal(PetitionEventData $event): bool
    {
        return $event->type === PetitionEventType::UNSPECIFIED_ADJOURNMENT_END
            && $event->reasoning === AdjournmentEndReason::Withdrawal->value;
    }

    /**
     * @return list<string>
     */
    private function eventVariants(PetitionEventData $event): array
    {
        return array_values(array_filter([
            $event->suspensionType?->value,
            $event->resultType?->value,
        ]));
    }

    private function nonStatutoryDecisionPeriodType(EventCalendarDay $day): ?DecisionPeriodType
    {
        if ($day->applicableTerm !== TermType::DECISION_PERIOD->value) {
            return null;
        }

        if ($day->decisionPeriodType === DecisionPeriodType::STATUTORY) {
            return null;
        }

        return $day->decisionPeriodType;
    }
}
