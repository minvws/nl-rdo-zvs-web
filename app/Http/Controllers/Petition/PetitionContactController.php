<?php

declare(strict_types=1);

namespace App\Http\Controllers\Petition;

use App\Actions\Petition\ContactPetitionUpdateAction;
use App\Actions\Petition\PetitionContactAttachAction;
use App\Actions\Petition\PetitionContactDetachAction;
use App\Enums\Ability;
use App\Enums\ContactRole;
use App\Enums\RouteName;
use App\Http\Requests\Contact\ContactAttachRequest;
use App\Http\Requests\Contact\ContactPetitionUpdateRequest;
use App\Http\Requests\FormRequest;
use App\Models\Builder\Contact\ContactQueryBuilder;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

use function __;
use function abort;

final readonly class PetitionContactController
{
    public function __construct(
        private Factory $view,
        private Redirector $redirector,
        #[Config('app.pagination.contact_items_per_page')]
        private int $paginationItemsPerPage,
        private Gate $gate,
    ) {
    }

    public function attachContact(
        ContactAttachRequest $request,
        Department $department,
        Petition $petition,
        Contact $contact,
        PetitionContactAttachAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        // Security check: ensure petition and contact belong to the same department
        if (
            (string) $petition->department_id !== (string) $department->id
            || (string) $contact->department_id !== (string) $department->id
        ) {
            abort(404);
        }

        $this->gate->authorize(Ability::UPDATE, $petition);

        $role = $request->getEnum('role', ContactRole::class);

        $action->execute($role, $petition, $contact, $user);

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM, [
            'department' => $department,
            'petition' => $petition,
        ])->with('message.success', __('general.saved'));
    }

    public function detachContact(
        FormRequest $request,
        Department $department,
        Petition $petition,
        Contact $contact,
        PetitionContactDetachAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $this->gate->authorize(Ability::UPDATE, $petition);

        $action->execute($request->getEnum('role', ContactRole::class), $petition, $contact, $user);

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM, [
            'department' => $department,
            'petition' => $petition,
        ])->with('message.success', __('general.saved'));
    }

    public function updateContactPivot(
        ContactPetitionUpdateRequest $request,
        Department $department,
        Petition $petition,
        Contact $contact,
        ContactPetitionUpdateAction $action,
        #[CurrentUser]
        User $user,
    ): RedirectResponse {
        $this->gate->authorize(Ability::UPDATE, $petition);

        $action->execute($petition, $contact, $user, $request->validated());

        return $this->redirector->route(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM, [
            'department' => $department,
            'petition' => $petition,
        ])->with('message.success', __('general.saved'));
    }

    public function showAttachForm(Request $request, Department $department, Petition $petition): View
    {
        $this->gate->authorize(Ability::UPDATE, $petition);

        $unassignedContacts = $this->getUnassignedContacts($petition);
        $paginator = ContactQueryBuilder::make()
            ->where(static function ($query) use ($unassignedContacts): void {
                $query->setQuery($unassignedContacts->getQuery());
            })
            ->notArchived()
            ->paginate($this->paginationItemsPerPage);

        return $this->view->make('petition.contacts.attach_contacts', [
            'petition' => $petition,
            'contacts' => $paginator->getCollection(),
            'search' => $request->query->getString('search'),
            'paginator' => $paginator->withQueryString(),
        ]);
    }

    /**
     * @return Builder<Contact>
     */
    private function getUnassignedContacts(Petition $petition): Builder
    {
        $assignedContactIds = $petition->contacts()
            ->wherePivot('role', '!=', ContactRole::STAKEHOLDER)
            ->pluck('contacts.id')->toArray();

        return Contact::query()
            ->whereDepartment($petition->department)
            ->notArchived()
            ->whereNotIn('id', $assignedContactIds);
    }
}
