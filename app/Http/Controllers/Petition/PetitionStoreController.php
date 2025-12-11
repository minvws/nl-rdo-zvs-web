<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Petition\PetitionCreateAction;
use App\Enums\Ability;
use App\Enums\RouteName;
use App\Http\Requests\Petition\PetitionCreateRequest;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionType;
use App\Models\User;
use App\Services\Petition\PetitionException;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\RedirectResponse;
use Throwable;

use function __;
use function to_route;

final readonly class PetitionStoreController
{
    public function __construct(
        private Gate $gate,
    ) {
    }

    /**
     * @throws PetitionException
     * @throws Throwable
     */
    public function __invoke(
        Department $department,
        PetitionType $petitionType,
        PetitionCreateRequest $petitionCreateRequest,
        PetitionCreateAction $action,
        #[CurrentUser] User $user,
    ): RedirectResponse {
        $this->gate->authorize(Ability::CREATE, [Petition::class, $department]);
        $petition = $action->execute($department, $user, $petitionType, $petitionCreateRequest->validated());

        return to_route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ])->with('message.success', __('general.saved'));
    }
}
