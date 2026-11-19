<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\NotificationRule\CreateNotificationRuleAction;
use App\Actions\NotificationRule\UpdateNotificationRuleAction;
use App\Enums\NotificationRuleAnchor;
use App\Enums\NotificationRuleTiming;
use App\Enums\PetitionEventType;
use App\Enums\RouteName;
use App\Http\Requests\NotificationRule\NotificationRuleRequest;
use App\Models\Department;
use App\Models\NotificationRule;
use App\Services\NotificationRule\NotificationRuleEventTypes;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

use function __;

final readonly class NotificationRuleController
{
    public function __construct(
        private Redirector $redirector,
        private Factory $view,
        private NotificationRuleEventTypes $eventTypes,
        #[Config('app.pagination.items_per_page')]
        private int $paginationItemsPerPage,
    ) {
    }

    public function index(Department $department): View
    {
        $notificationRules = NotificationRule::query()
            ->forDepartment($department)
            ->oldest()
            ->paginate($this->paginationItemsPerPage);

        return $this->view->make('notification-rules.index', [
            'notificationRules' => $notificationRules,
            'eventTypes' => $this->eventTypes->forDepartment($department),
            'department' => $department,
        ]);
    }

    public function create(Department $department): View
    {
        return $this->view->make('notification-rules.create', [
            'eventTypes' => $this->eventTypes->forDepartment($department),
            'department' => $department,
        ]);
    }

    public function store(
        Department $department,
        NotificationRuleRequest $notificationRuleRequest,
        CreateNotificationRuleAction $createNotificationRuleAction,
    ): RedirectResponse {
        $createNotificationRuleAction->execute($department, $notificationRuleRequest->toNotificationRuleData());

        return $this->redirector->route(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_INDEX, ['department' => $department])
            ->with('message.success', __('general.saved'));
    }

    public function edit(Department $department, NotificationRule $notificationRule): View
    {
        return $this->view->make('notification-rules.edit', [
            'notificationRule' => $notificationRule,
            'eventTypes' => $this->eventTypes->forDepartment($department),
            'department' => $department,
        ]);
    }

    public function update(
        Department $department,
        NotificationRule $notificationRule,
        NotificationRuleRequest $notificationRuleRequest,
        UpdateNotificationRuleAction $updateNotificationRuleAction,
    ): RedirectResponse {
        $updateNotificationRuleAction->execute($notificationRule, $notificationRuleRequest->toNotificationRuleData());

        return $this->redirector->route(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_INDEX, ['department' => $department])
            ->with('message.success', __('general.saved'));
    }

    public function delete(Department $department, NotificationRule $notificationRule): RedirectResponse
    {
        $notificationRule->delete();

        return $this->redirector->route(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_INDEX, ['department' => $department])
            ->with('message.success', __('general.deleted'));
    }

    /**
     * Renders the mail a rule would send, from the form as it is filled in so far.
     */
    public function preview(Department $department, Request $request): View
    {
        $eventType = PetitionEventType::tryFrom($request->string('event_type')->toString());
        $anchor = NotificationRuleAnchor::tryFrom($request->string('anchor')->toString());
        $timing = NotificationRuleTiming::tryFrom($request->string('timing')->toString());
        $isComplete = $eventType instanceof PetitionEventType
            && $anchor instanceof NotificationRuleAnchor
            && $timing instanceof NotificationRuleTiming
            && $this->eventTypes->forDepartment($department)->has($eventType->value);

        return $this->view->make('notification-rules.preview', [
            'notificationRule' => $isComplete ? new NotificationRule([
                'event_type' => $eventType,
                'anchor' => $anchor,
                'offset_days' => $timing->offset($request->integer('days')),
            ]) : null,
            'eventTypes' => $this->eventTypes->forDepartment($department),
        ]);
    }
}
