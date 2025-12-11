<?php

declare(strict_types=1);

namespace App\Http\Controllers\Contact;

use App\Actions\Contact\ContactArchiveAction;
use App\Enums\RouteName;
use App\Models\Contact;
use App\Models\Department;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Throwable;

final readonly class ContactArchiveController
{
    public function __construct(
        private Redirector $redirector,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function store(
        Department $department,
        Contact $contact,
        ContactArchiveAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $action->execute($contact, $user);

        return $this->redirector->route(RouteName::DEPARTMENTS_CONTACTS_SHOW, [
            'department' => $department,
            'contact' => $contact,
        ]);
    }
}
