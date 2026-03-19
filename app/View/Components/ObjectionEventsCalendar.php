<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Services\DerivedState;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\WizardEventCollection;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use function view;

class ObjectionEventsCalendar extends Component
{
    private readonly EventCalendar $calendar;

    public function __construct(
        private readonly WizardEventCollection $events,
    ) {
        $state = new DerivedState();
        $state->addEvents($this->events->all());
        $state->buildCalendar();
        $this->calendar = $state->getCalendar();
    }

    public function render(): View|Closure|string
    {
        return view('components.objection-events-calendar', [
            'calendarItems' => $this->calendar,
        ]);
    }
}
