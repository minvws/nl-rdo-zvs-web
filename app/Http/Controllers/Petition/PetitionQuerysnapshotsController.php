<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Petition\QuerysnapshotUpdateAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\ExternalUrl\QuerysnapshotUpdateRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use App\View\HtmxHelper;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Redirector;
use Illuminate\View\Factory;

use function __;
use function array_values;

final readonly class PetitionQuerysnapshotsController
{
    public function __construct(
        private HtmxHelper $htmxHelper,
        private Factory $view,
        private Redirector $redirector,
        private Repository $repository,
    ) {
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function edit(Request $request, Department $department, Petition $petition): Response
    {
        $petitionVariant = $petition->petitionType->type;

        $availableQuerysnapshotTypes = $this->repository->array('querysnapshot.' . $petitionVariant->value, []);
        $availableQuerysnapshotTypes = array_values($availableQuerysnapshotTypes);

        return $this->htmxHelper->makeFormViewResponse($request, 'petition.querysnapshots.edit', [
            'petition' => $petition,
            'availableQuerysnapshotTypes' => $availableQuerysnapshotTypes,
            'department' => $department,
        ]);
    }

    #[Authorize(Ability::UPDATE, 'petition')]
    public function update(
        Request $request,
        Department $department,
        Petition $petition,
        QuerysnapshotUpdateRequest $querysnapshotUpdateRequest,
        QuerysnapshotUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse|View {
        /** @var array{querysnapshots: list<array{querysnapshot_type: string, querysnapshot_id: string}>} $data */
        $data = $querysnapshotUpdateRequest->validated();

        $action->execute($petition, $user, $data);

        $petition->refresh();

        if ($this->htmxHelper->isHtmxRequest($request)) {
            return $this->show($department, $petition);
        }

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])
            ->with('message.success', __('general.saved'));
    }

    #[Authorize(Ability::VIEW, 'petition')]
    public function show(Department $department, Petition $petition): View
    {
        return $this->view->make('petition.querysnapshots.show', [
            'petition' => $petition,
        ]);
    }
}
