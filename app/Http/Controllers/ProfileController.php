<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\User\ProfileUpdateAction;
use App\Http\Requests\User\ProfileUpdateRequest;
use App\Models\User;
use App\Services\User\UserException;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

use function __;

final readonly class ProfileController
{
    public function __construct(
        private Redirector $redirector,
        private Factory $view,
    ) {
    }

    public function edit(#[CurrentUser] User $user): View
    {
        return $this->view->make('profile.edit', [
            'user' => $user,
        ]);
    }

    /**
     * @throws UserException
     */
    public function update(
        ProfileUpdateRequest $request,
        ProfileUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $action->execute($user, $request->validated());

        return $this->redirector->route('profile.edit')
            ->with('message.success', __('general.saved'));
    }
}
