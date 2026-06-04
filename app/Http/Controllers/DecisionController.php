<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Decision\DecisionCreateAction;
use App\Actions\Decision\DecisionUpdateAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\Decision\DecisionCreateRequest;
use App\Http\Requests\Decision\DecisionUpdateRequest;
use App\Models\Decision;
use App\Models\Department;
use App\Models\Petition;
use App\Models\Team;
use App\Models\User;
use App\Services\Decision\DecisionFilterService;
use App\Services\Decision\DecisionIndexService;
use App\Services\Timeline\TimelineFilterService;
use App\View\HtmxHelper;
use Illuminate\Config\Repository;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\UrlGenerator;
use Webmozart\Assert\Assert;

use function __;
use function in_array;
use function sprintf;

final readonly class DecisionController
{
    public function __construct(
        private Factory $view,
        private Redirector $redirector,
        private HtmxHelper $htmxHelper,
        private UrlGenerator $urlGenerator,
        private TimelineFilterService $timelineFilterService,
        private DecisionFilterService $decisionFilterService,
        private DecisionIndexService $decisionIndexService,
        private Repository $repository,
    ) {
    }

    #[Authorize(Ability::CREATE, Decision::class)]
    public function create(Department $department, ?Petition $petition = null): View
    {
        return $this->view->make('petition.decision.create', [
            'department' => $department,
            'petition' => $petition,
            'teams' => Team::query()
                ->where('department_id', $department->id)
                ->active()
                ->orderBy('name')
                ->get(),
        ]);
    }

    #[Authorize(Ability::CREATE, Decision::class)]
    public function store(
        Department $department,
        DecisionCreateRequest $request,
        DecisionCreateAction $action,
        #[CurrentUser]
        User $user,
        ?Petition $petition = null,
    ): RedirectResponse {
        $decision = $action->execute($department, $user, $request->validated(), $petition);

        return $this->redirector->to($this->urlGenerator->route(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
            'department' => $department,
            'decision' => $decision,
        ]))
            ->with('message.success', __('general.saved'));
    }

    #[Authorize(Ability::VIEW, 'decision')]
    public function show(Department $department, Decision $decision, Request $request): View
    {
        $timelineFilterGroups = $this->timelineFilterService->availableGroupsFor($decision->timelineItems);
        $timelineFilterGroup = $request->input('timeline_filter_group');
        $decisionTimelineItems = $decision->timelineItems;

        if ($timelineFilterGroup && $timelineFilterGroup !== 'no_selection') {
            Assert::string($timelineFilterGroup);
            $allowedTypes = $this->repository->array(sprintf('timeline_filters.groups.%s', $timelineFilterGroup));
            $decisionTimelineItems = $decisionTimelineItems->filter(static function ($item) use ($allowedTypes): bool {
                return in_array($item->type->value, $allowedTypes, true);
            });
        }

        return $this->view->make('decision.show', [
            'decision' => $decision,
            'decisionTimelineItems' => $decisionTimelineItems,
            'timelineFilterGroups' => $timelineFilterGroups,
            'selectedTimelineFilter' => $timelineFilterGroup ?? 'no_selection',
        ]);
    }

    #[Authorize(Ability::UPDATE, 'decision')]
    public function edit(Request $request, Department $department, Decision $decision): Response
    {
        return $this->htmxHelper->makeFormViewResponse($request, 'decision.properties.edit', [
            'decision' => $decision,
            'department' => $department,
            'teams' => Team::query()
                ->where('department_id', $department->id)
                ->active()
                ->orderBy('name')
                ->get(),
        ]);
    }

    #[Authorize(Ability::UPDATE, 'decision')]
    public function update(
        Department $department,
        Decision $decision,
        DecisionUpdateRequest $request,
        DecisionUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse|View {
        $action->execute($decision, $user, $request->validated());

        if ($this->htmxHelper->isHtmxRequest($request)) {
            return $this->view->make('decision.properties.show', [
                'decision' => $decision,
                'department' => $department,
            ]);
        }

        return $this->redirector->route(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
            'department' => $department,
            'decision' => $decision,
        ])
            ->with('message.success', __('general.saved'));
    }

    public function showProperties(Department $department, Decision $decision): View
    {
        return $this->view->make('decision.properties.show', [
            'department' => $department,
            'decision' => $decision,
        ]);
    }

    #[Authorize(Ability::VIEW_ANY, Decision::class)]
    public function index(Request $request, Department $department, #[CurrentUser] User $user): View|RedirectResponse
    {
        $redirectResponse = $this->decisionFilterService->handleFilterPersistence($request, $user, $department);
        if ($redirectResponse instanceof RedirectResponse) {
            return $redirectResponse;
        }

        $indexData = $this->decisionIndexService->getIndexData($request, $department, $user);

        return $this->view->make('decision.index', $indexData);
    }

    public function filter(Request $request, Department $department): RedirectResponse
    {
        return $this->redirector->route(
            RouteName::DEPARTMENTS_DECISIONS_INDEX,
            [
                'department' => $department,
                'filter' => $request->input('filter'),
            ],
        );
    }
}
