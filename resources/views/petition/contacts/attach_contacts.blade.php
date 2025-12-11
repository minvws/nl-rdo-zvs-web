@use(App\Enums\RouteName)
@use(App\Enums\ContactCriteria)
@use(App\Enums\ContactRole)
@use(App\Enums\CorrespondencePreference)

@section('pageTitle', __('contact.attach_contacts'))

<x-app-layout>
    <x-petition-header-details
        :petition="$petition"
        :hasBackLink="true"
        :backLinkRoute="route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $petition->department, 'petition' => $petition])"
        :backLinkLabel="__('general.back_to_petition')" />

    <section class="mt-5">
        <x-contacts.search-box
            :action="route(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM_FILTER, ['department' => $petition->department,
                'petition' => $petition,
            ])" />
    </section>

    <x-contacts.attached-contacts-table
        :title="__('contact.attached_applicant')"
        :contacts="$petition->applicant"
        :contactRole="ContactRole::APPLICANT"
        :petition="$petition"
        :emptyMessage="__('contact.no_applicants')" />

    <x-contacts.attached-contacts-table
        :title="__('contact.attached_representative')"
        :contacts="$petition->representative"
        :contactRole="ContactRole::REPRESENTATIVE"
        :petition="$petition"
        :emptyMessage="__('contact.no_representatives')" />

    <x-contacts.attached-contacts-table
        :title="__('contact.attached_institution')"
        :contacts="$petition->institution"
        :contactRole="ContactRole::INSTITUTION"
        :petition="$petition"
        :emptyMessage="__('contact.no_institutions')" />

    <x-contacts.attached-contacts-table
        :title="__('contact.attached_stakeholders')"
        :contacts="$petition->stakeholders"
        :contactRole="ContactRole::STAKEHOLDER"
        :petition="$petition"
        :emptyMessage="__('contact.no_stakeholders')" />

    <section class="mt-5">
        <div class="visually-grouped">
            <h2>{{ __('contact.attach_contacts') }}</h2>
            @if ($petition->applicant->isNotEmpty() || $petition->representative->isNotEmpty() || $petition->institution->isNotEmpty())
                <p><em>{{ __('contact.attach_other') }}</em></p>
            @endif

            <table class="contacts-table">
                <thead>
                    <tr>
                        <th scope="col">{{ __('contact.last_name') }}</th>
                        <th scope="col">{{ __('contact.organisation_name') }}</th>
                        <th scope="col">
                            <span class="visually-hidden">{{ __('general.actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @if ($contacts->isEmpty())
                        <tr>
                            <td colspan="3">{{ __('contact.no_contacts') }}</td>
                        </tr>
                    @else
                        @foreach ($contacts as $contact)
                            <tr>
                                <td scope="row">
                                    <a
                                        href="{{ route(RouteName::DEPARTMENTS_CONTACTS_SHOW, ['department' => $petition->department, 'contact' => $contact]) }}">
                                        {{ $contact->last_name }}
                                    </a>
                                </td>
                                <td>
                                    <a
                                        href="{{ route(RouteName::DEPARTMENTS_CONTACTS_SHOW, ['department' => $petition->department, 'contact' => $contact]) }}">
                                        {{ $contact->organisation_name }}
                                    </a>
                                </td>

                                <td class="button-cell">
                                    <div class="button-container">
                                        <form
                                            action="{{
                                                route(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH, [
                                                    'department' => $petition->department,
                                                    'petition' => $petition,
                                                    'contact' => $contact,
                                                ])
                                            }}"
                                            method="POST">
                                            @csrf
                                            <input
                                                type="hidden"
                                                name="role"
                                                value="{{ ContactRole::APPLICANT->value }}" />

                                            @if ($petition->applicant->isNotEmpty() || $petition->stakeholders->contains($contact))
                                                <x-secondary-button
                                                    disabled
                                                    aria-disabled="true">
                                                    {{ __('contact.attached_applicant') }}
                                                </x-secondary-button>
                                            @else
                                                <x-secondary-button>
                                                    {{ __('contact.attached_applicant') }}
                                                </x-secondary-button>
                                            @endif
                                        </form>
                                        <form
                                            action="{{
                                                route(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH, [
                                                    'department' => $petition->department,
                                                    'petition' => $petition,
                                                    'contact' => $contact,
                                                ])
                                            }}"
                                            method="POST">
                                            @csrf
                                            <input
                                                type="hidden"
                                                name="role"
                                                value="{{ ContactRole::REPRESENTATIVE->value }}" />

                                            @if ($petition->representative->isNotEmpty() || $petition->stakeholders->contains($contact))
                                                <x-secondary-button
                                                    disabled
                                                    aria-disabled="true">
                                                    {{ __('contact.attached_representative') }}
                                                </x-secondary-button>
                                            @else
                                                <x-secondary-button>
                                                    {{ __('contact.attached_representative') }}
                                                </x-secondary-button>
                                            @endif
                                        </form>
                                        <form
                                            action="{{
                                                route(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH, [
                                                    'department' => $petition->department,
                                                    'petition' => $petition,
                                                    'contact' => $contact,
                                                ])
                                            }}"
                                            method="POST">
                                            @csrf
                                            <input
                                                type="hidden"
                                                name="role"
                                                value="{{ ContactRole::INSTITUTION->value }}" />

                                            @if ($petition->institution->isNotEmpty() || $petition->stakeholders->contains($contact))
                                                <x-secondary-button
                                                    disabled
                                                    aria-disabled="true">
                                                    {{ __('contact.attached_institution') }}
                                                </x-secondary-button>
                                            @else
                                                <x-secondary-button>
                                                    {{ __('contact.attached_institution') }}
                                                </x-secondary-button>
                                            @endif
                                        </form>
                                        <form
                                            action="{{
                                                route(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH, [
                                                    'department' => $petition->department,
                                                    'petition' => $petition,
                                                    'contact' => $contact,
                                                ])
                                            }}"
                                            method="POST">
                                            @csrf
                                            <input
                                                type="hidden"
                                                name="role"
                                                value="{{ ContactRole::STAKEHOLDER->value }}" />
                                            @if ($petition->stakeholders->contains($contact))
                                                <x-secondary-button
                                                    disabled
                                                    aria-disabled="true">
                                                    {{ __('contact.stakeholder') }}
                                                </x-secondary-button>
                                            @else
                                                <x-secondary-button>
                                                    {{ __('contact.stakeholder') }}
                                                </x-secondary-button>
                                            @endif
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
                <tfoot>
                    <tr></tr>
                </tfoot>
            </table>
        </div>
        {{ $paginator->links() }}
    </section>
</x-app-layout>
