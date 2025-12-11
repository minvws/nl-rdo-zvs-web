<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Config\Config;
use App\Enums\Ability;
use App\Enums\TimelineFilterGroup;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use App\Services\Petition\PetitionTimelineService;
use App\Services\Timeline\TimelineFilterService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

use function sprintf;

final readonly class PetitionShowController
{
    public function __construct(
        private Factory $view,
        private Gate $gate,
        private PetitionTimelineService $petitionTimelineService,
        private TimelineFilterService $timelineFilterService,
    ) {
    }

    public function __invoke(Department $department, Petition $petition, Request $request, #[CurrentUser] User $user): View
    {
        $this->gate->authorize(Ability::VIEW, $petition);
        $petitionTypeConfiguration = Config::array(
            sprintf('petition_type_type.%s.optional_form_fields', $petition->petitionType->type->value),
        );
        $availableCostTypes = Config::array(
            sprintf('custom_cost.%s', $petition->petitionType->type->value),
        );
        $timelineFilterGroup = $request->enum('timeline_filter_group', TimelineFilterGroup::class);
        $timelineFilterGroups = $this->timelineFilterService->availableGroupsFor($petition->timelineItems);
        $petitionTimelineItems = $this->petitionTimelineService->getFilteredTimelineItems($petition, $timelineFilterGroup);

        return $this->view->make('petition.show', [
            'availableCostTypes' => new Collection($availableCostTypes),
            'department' => $department,
            'petition' => $petition,
            'petitionTypeConfiguration' => $petitionTypeConfiguration,
            'users' => User::query()->active()->get(),
            'user' => $user,
            'petitionTimelineItems' => $petitionTimelineItems,
            'timelineFilterGroups' => $timelineFilterGroups,
            'selectedTimelineFilter' => $timelineFilterGroup->value ?? 'no_selection',
        ]);
    }
}
