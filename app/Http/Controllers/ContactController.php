<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Contact\CreateContact;
use App\Actions\Contact\UpdateContact;
use App\Enums\RouteName;
use App\Http\Requests\Contact\ContactPersistRequest;
use App\Models\Builder\Contact\ContactQueryBuilder;
use App\Models\Contact;
use App\Models\Department;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Redirector;

use function __;
use function array_merge;

final readonly class ContactController
{
    public function __construct(
        private Factory $view,
        private Redirector $redirector,
        #[Config('app.pagination.contact_items_per_page')]
        private int $paginationItemsPerPage,
    ) {
    }

    public function filter(Request $request, Department $department): RedirectResponse
    {
        return $this->redirector->route(
            RouteName::DEPARTMENTS_CONTACTS_INDEX,
            [
                'department' => $department,
                'filter' => $request->input('filter'),
            ],
        );
    }

    public function index(Request $request, Department $department): View
    {
        /** @var LengthAwarePaginator<int, Contact> $paginator */
        $paginator = ContactQueryBuilder::make()
            ->where('department_id', $department->id)
            ->notArchived()
            ->paginate($this->paginationItemsPerPage);

        return $this->view->make('contacts.index', [
            'contacts' => $paginator->getCollection(),
            'search' => $request->query->getString('search'),
            'paginator' => $paginator->withQueryString(),
            'department' => $department,
        ]);
    }

    public function edit(Department $department, Contact $contact): View
    {
        return $this->view->make('contacts.edit', [
            'contact' => $contact,
            'department' => $department,
        ]);
    }

    public function show(Department $department, Contact $contact): View
    {
        return $this->view->make('contacts.show', [
            'contact' => $contact,
            'department' => $department,
        ]);
    }

    public function update(
        Department $department,
        Contact $contact,
        ContactPersistRequest $contactPersistRequest,
        UpdateContact $action,
    ): RedirectResponse {
        $action->execute($contact, $contactPersistRequest->validated());

        return $this->redirector->route(RouteName::DEPARTMENTS_CONTACTS_SHOW, [
            'department' => $department,
            'contact' => $contact,
        ])
            ->with('message.success', __('general.saved'));
    }

    public function create(Department $department): View
    {
        return $this->view->make('contacts.create', [
            'department' => $department,
        ]);
    }

    public function store(ContactPersistRequest $contactPersistRequest, Department $department, CreateContact $action): RedirectResponse
    {
        $data = array_merge($contactPersistRequest->validated(), [
            'department_id' => $department->id,
        ]);

        $contact = $action->execute($data);

        return $this->redirector->route(RouteName::DEPARTMENTS_CONTACTS_SHOW, [
            'department' => $department,
            'contact' => $contact,
        ])
            ->with('message.success', __('general.saved'));
    }
}
