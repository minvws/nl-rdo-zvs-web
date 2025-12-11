<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Timelineable\TimelineableNoteCreateAction;
use App\Enums\Ability;
use App\Http\Requests\Timelineable\TimelineableNoteCreateRequest;
use App\Models\Contracts\TimelineableInterface;
use App\Models\Department;
use App\Models\EloquentModel;
use App\Models\User;
use App\View\HtmxHelper;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\View\View;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Webmozart\Assert\Assert;

use function __;

final readonly class TimelineableNoteController
{
    public function __construct(
        private Redirector $redirector,
        private HtmxHelper $htmxHelper,
        private Encrypter $encrypter,
        private Gate $gate,
    ) {
    }

    public function create(
        Request $request,
        Department $department,
        string $timelineableType,
        TimelineableInterface $timelineable,
        string $url,
    ): Response {
        $this->gate->authorize(Ability::UPDATE, $timelineable);

        Assert::isInstanceOf($timelineable, EloquentModel::class);

        return $this->htmxHelper->makeFormViewResponse($request, 'petition.petition-note.create', [
            'timelineableType' => $timelineableType,
            'timelineableId' => $timelineable->getKey(),
            'url' => $url,
            'department' => $department,
        ]);
    }

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
        $this->gate->authorize(Ability::UPDATE, $timelineable);

        $action->execute($timelineable, $user, $timelineableNoteCreateRequest->validated());

        if ($this->htmxHelper->isHtmxRequest($timelineableNoteCreateRequest)) {
            $response = new Response();
            $response->headers->set('HX-Trigger', 'eventPetitionUpdated-timeline');

            return $response;
        }

        return $this->redirector->to($this->encrypter->decryptString($url))
            ->with('message.success', __('general.saved'));
    }
}
