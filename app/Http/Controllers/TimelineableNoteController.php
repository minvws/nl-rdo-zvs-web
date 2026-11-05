<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Timelineable\TimelineableNoteCreateAction;
use App\Actions\Timelineable\TimelineableNoteDeleteAction;
use App\Enums\Ability;
use App\Enums\TimelineType;
use App\Http\Requests\Timelineable\TimelineableNoteCreateRequest;
use App\Http\Requests\Timelineable\TimelineableNoteDeleteRequest;
use App\Models\Contracts\TimelineableInterface;
use App\Models\Department;
use App\Models\EloquentModel;
use App\Models\TimelineItem;
use App\Models\User;
use App\View\HtmxHelper;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\View;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;
use Illuminate\View\Factory;
use Webmozart\Assert\Assert;

use function __;

final readonly class TimelineableNoteController
{
    public function __construct(
        private Redirector $redirector,
        private HtmxHelper $htmxHelper,
        private Encrypter $encrypter,
        private Factory $view,
    ) {
    }

    #[Authorize(Ability::UPDATE, 'timelineable')]
    public function create(
        Request $request,
        Department $department,
        string $timelineableType,
        TimelineableInterface $timelineable,
        string $url,
    ): Response {
        Assert::isInstanceOf($timelineable, EloquentModel::class);

        return $this->htmxHelper->makeFormViewResponse($request, 'petition.petition-note.create', [
            'timelineableType' => $timelineableType,
            'timelineableId' => $timelineable->getKey(),
            'url' => $url,
            'department' => $department,
        ]);
    }

    #[Authorize(Ability::UPDATE, 'timelineable')]
    public function store(
        Department $department,
        string $timelineableType,
        TimelineableInterface $timelineable,
        string $url,
        TimelineableNoteCreateRequest $timelineableNoteCreateRequest,
        TimelineableNoteCreateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse|View|Response {
        $action->execute($timelineable, $user, $timelineableNoteCreateRequest->validated());

        if ($this->htmxHelper->isHtmxRequest($timelineableNoteCreateRequest)) {
            $response = new Response();
            $response->headers->set('HX-Trigger', 'eventPetitionUpdated-timeline');

            return $response;
        }

        return $this->redirector->to($this->encrypter->decryptString($url))
            ->with('message.success', __('general.saved'));
    }

    #[Authorize(Ability::UPDATE, 'timelineable')]
    public function confirmDelete(
        Department $department,
        string $timelineableType,
        TimelineableInterface $timelineable,
        string $url,
        int $timelineItem,
    ): View {
        Assert::isInstanceOf($timelineable, EloquentModel::class);

        return $this->view->make('petition.petition-note.confirm-delete', [
            'department' => $department,
            'timelineableType' => $timelineableType,
            'timelineableId' => $timelineable->getKey(),
            'timelineItem' => $this->findNote($timelineable, $timelineItem),
            'url' => $url,
        ]);
    }

    #[Authorize(Ability::UPDATE, 'timelineable')]
    public function delete(
        Department $department,
        string $timelineableType,
        TimelineableInterface $timelineable,
        string $url,
        int $timelineItem,
        TimelineableNoteDeleteRequest $request,
        TimelineableNoteDeleteAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $note = $this->findNote($timelineable, $timelineItem);

        $reason = $request->validated('reason');
        Assert::nullOrString($reason);

        $action->execute($timelineable, $note, $user, $reason);

        return $this->redirector->to($this->encrypter->decryptString($url))
            ->with('message.success', __('general.deleted'));
    }

    private function findNote(TimelineableInterface $timelineable, int $timelineItemId): TimelineItem
    {
        $note = $timelineable->timelineItems()
            ->where('type', TimelineType::NOTE)
            ->where('internal_id', $timelineItemId)
            ->firstOrFail();
        Assert::isInstanceOf($note, TimelineItem::class);

        return $note;
    }
}
