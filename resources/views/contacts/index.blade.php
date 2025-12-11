@use(App\Enums\ContactCriteria)
@section('pageTitle', __('contact.contacts_in') . ' ' . ActiveDepartment::getActiveDepartment()?->name)

<x-app-layout>
    <x-slot name="header">
        <div class="action-bar">
            <h1>
                {{ __('contact.model_plural') }}
            </h1>

            @can(Permission::PETITION_WRITE->value)
                <a
                    class="button"
                    href="{{ route(RouteName::DEPARTMENTS_CONTACTS_CREATE, ['department' => $department]) }}">
                    {{ __('contact.create') }}
                    <x-tabler-plus
                        aria-hidden="true"
                        focusable="false" />
                </a>
            @endcan
        </div>
    </x-slot>

    <section class="mt-5">
        @can(\App\Enums\Authorization\Permission::CONTACT_MANAGE)
            <x-contacts.search-box
                :action="route(RouteName::DEPARTMENTS_CONTACTS_INDEX_FILTER, ['department' => $department])" />
        @endcan
    </section>

    @can(\App\Enums\Authorization\Permission::CONTACT_MANAGE)
        <section class="mt-5">
            <div class="visually-grouped">
                <table>
                    <caption class="visually-hidden">
                        {{ __('contact.model_plural') }}
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">{{ __('contact.last_name') }}</th>
                            <th scope="col">{{ __('contact.initials') }}</th>
                            <th scope="col">{{ __('contact.organisation_name') }}</th>
                            <th scope="col">{{ __('contact.email_address') }}</th>
                            <th scope="col">{{ __('contact.city') }}</th>
                            <th>&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contacts as $contact)
                            <tr class="table-row-clickable">
                                <th scope="row">
                                    {{ implode(', ', array_filter([$contact->last_name, $contact->middle_name])) }}
                                </th>
                                <td>{{ $contact->initials }}</td>
                                <td>{{ $contact->organisation_name }}</td>
                                <td>{{ $contact->email_address }}</td>
                                <td>{{ $contact->city }}</td>
                                <td>
                                    @can(Permission::CONTACT_READ->value)
                                        <a
                                            class="icon-only"
                                            href="{{ route(RouteName::DEPARTMENTS_CONTACTS_SHOW, ['department' => $department, 'contact' => $contact]) }}"
                                            aria-label="{{ __('contact.details') }} {{ $contact->last_name }} {{ __('general.view') }}">
                                            <x-tabler-chevron-right
                                                aria-hidden="true"
                                                focusable="false" />
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">{{ __('contact.no_contacts') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr></tr>
                    </tfoot>
                </table>
                {{ $paginator->links() }}
            </div>
        </section>
    @else
        <p>{{ __('contact.no_permission_to_view_contacts') }}</p>
    @endcan
</x-app-layout>
