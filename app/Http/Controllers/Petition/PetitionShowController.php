<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Config\Config;
use App\Enums\Ability;
use App\Enums\TermType;
use App\Enums\TimelineFilterGroup;
use App\Factories\WizardEventCollectionFactory;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use App\Services\DerivedState;
use App\Services\Petition\PetitionTimelineService;
use App\Services\Timeline\TimelineFilterService;
use App\ValueObjects\CalendarDate;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;

use function sprintf;

final readonly class PetitionShowController
{
    public function __construct(
        private Factory $view,
        private PetitionTimelineService $petitionTimelineService,
        private TimelineFilterService $timelineFilterService,
        private WizardEventCollectionFactory $eventCollectionFactory,
        private DerivedState $derivedState,
    ) {
    }

    #[Authorize(Ability::VIEW, 'petition')]
    public function __invoke(Department $department, Petition $petition, Request $request, #[CurrentUser] User $user): View
    {
        $petitionTypeConfiguration = Config::array(
            sprintf('petition_variant.%s.optional_form_fields', $petition->petitionType->type->value),
        );
        $availableCostTypes = Config::array(
            sprintf('custom_cost.%s', $petition->petitionType->type->value),
        );
        $timelineFilterGroup = $request->enum('timeline_filter_group', TimelineFilterGroup::class);
        $timelineFilterGroups = $this->timelineFilterService->availableGroupsFor($petition->timelineItems);
        $petitionTimelineItems = $this->petitionTimelineService->getFilteredTimelineItems($petition, $timelineFilterGroup);
        $events = $this->eventCollectionFactory::fromModels($petition->petitionEvents);
        $deadlineNoticeOfDefaultPenaltyPeriodEnd = null;
        $deadlineAppealNotTimelyPenaltyPeriodEnd = null;

        if ($petition->isTermEngineConverted()) {
            $this->derivedState->addEvents($events->all())->buildCalendar();
            $deadlineNoticeOfDefaultPenaltyPeriodEnd = $this->derivedState->penaltyPeriodEndDateForTerm(TermType::NOTICE_OF_DEFAULT);
            $deadlineAppealNotTimelyPenaltyPeriodEnd = $this->derivedState->penaltyPeriodEndDateForTerm(TermType::APPEAL_NOT_TIMELY);
        }

        Assert::nullOrIsInstanceOf($deadlineNoticeOfDefaultPenaltyPeriodEnd, CalendarDate::class);
        Assert::nullOrIsInstanceOf($deadlineAppealNotTimelyPenaltyPeriodEnd, CalendarDate::class);

        return $this->view->make('petition.show', [
            'availableCostTypes' => new Collection($availableCostTypes),
            'department' => $department,
            'deadlineNoticeOfDefaultPenaltyPeriodEnd' => $deadlineNoticeOfDefaultPenaltyPeriodEnd,
            'deadlineAppealNotTimelyPenaltyPeriodEnd' => $deadlineAppealNotTimelyPenaltyPeriodEnd,
            'petition' => $petition,
            'events' => $events,
            'petitionTypeConfiguration' => $petitionTypeConfiguration,
            'users' => User::query()->active()->get(),
            'user' => $user,
            'petitionTimelineItems' => $petitionTimelineItems,
            'timelineFilterGroups' => $timelineFilterGroups,
            'selectedTimelineFilter' => $timelineFilterGroup->value ?? 'no_selection',
        ]);
    }
}
