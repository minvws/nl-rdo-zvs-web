@use(App\Enums\Authorization\Permission)
@use(App\Enums\RouteName)

@section('pageTitle', __('contact.details') . ' ' . $contact->last_name)

<x-app-layout>
    <x-slot name="header">
        <div class="action-bar">
            <h1>
                {{ __('contact.show') }}
            </h1>
            @can(Permission::CONTACT_WRITE->value)
                <a
                    class="button"
                    href="{{ route(RouteName::DEPARTMENTS_CONTACTS_EDIT, ['department' => $department, 'contact' => $contact]) }}">
                    {{ __('contact.edit') }}
                    <x-tabler-edit
                        aria-hidden="true"
                        focusable="false" />
                </a>
            @endcan
        </div>
    </x-slot>
    <section class="mt-5">
        <div class="visually-grouped">
            <div class="contact-list">
                <div class="contact-list__column">
                    <dl>
                        <dt class="contact-list__label">{{ __('contact.initials') }}</dt>
                        <dd class="contact-list__value">{{ $contact->initials ?? '-' }}</dd>

                        <dt class="contact-list__label">{{ __('contact.last_name') }}</dt>
                        <dd class="contact-list__value">
                            {{ $contact->last_name ? implode(', ', array_filter([$contact->last_name, $contact->middle_name])) : '-' }}
                        </dd>

                        <dt class="contact-list__label">{{ __('contact.organisation_name') }}</dt>
                        <dd class="contact-list__value">{{ $contact->organisation_name ?? '-' }}</dd>

                        <dt class="contact-list__label">{{ __('contact.email_address') }}</dt>
                        <dd class="contact-list__value">{{ $contact->email_address ?? '-' }}</dd>

                        <dt class="contact-list__label">{{ __('contact.secondary_email_address') }}</dt>
                        <dd class="contact-list__value">{{ $contact->secondary_email_address ?? '-' }}</dd>

                        <dt class="contact-list__label">{{ __('contact.email_address_2') }}</dt>
                        <dd class="contact-list__value">{{ $contact->email_address_2 ?? '-' }}</dd>

                        <dt class="contact-list__label">{{ __('contact.email_address_3') }}</dt>
                        <dd class="contact-list__value">{{ $contact->email_address_3 ?? '-' }}</dd>

                        <dt class="contact-list__label">{{ __('contact.phone_number') }}</dt>
                        <dd class="contact-list__value">{{ $contact->phone_number ?? '-' }}</dd>
                    </dl>
                </div>
                <div class="contact-list__column">
                    <dl>
                        <dt class="contact-list__label">{{ __('contact.street') }}</dt>
                        <dd class="contact-list__value">{{ $contact->street ?? '-' }}</dd>

                        <dt class="contact-list__label">{{ __('contact.house_number') }}</dt>
                        <dd class="contact-list__value">{{ $contact->house_number ?? '-' }}</dd>

                        <dt class="contact-list__label">{{ __('contact.postal_code') }}</dt>
                        <dd class="contact-list__value">{{ $contact->postal_code ?? '-' }}</dd>

                        <dt class="contact-list__label">{{ __('contact.city') }}</dt>
                        <dd class="contact-list__value">{{ $contact->city ?? '-' }}</dd>

                        <dt class="contact-list__label">{{ __('contact.visiting_address') }}</dt>
                        <dd class="contact-list__value">
                            {{ $contact->visiting_address_street }} {{ $contact->visiting_address_house_number }}
                            <br />
                            {{ $contact->visiting_address_postal_code }} {{ $contact->visiting_address_city }}
                        </dd>

                        <dt class="contact-list__label">{{ __('contact.postal_address') }}</dt>
                        <dd class="contact-list__value">
                            {{ $contact->postal_address_street }} {{ $contact->postal_address_house_number }}
                            <br />
                            {{ $contact->postal_address_postal_code }} {{ $contact->postal_address_city }}
                        </dd>

                        <dt class="contact-list__label">{{ __('contact.type') }}</dt>
                        <dd class="contact-list__value">
                            {{ __('contact.contact_type_enum.' . $contact->type->value) }}
                        </dd>

                        <dt class="contact-list__label">{{ __('contact.notes') }}</dt>
                        <dd class="contact-list__value">{{ $contact->notes ?? '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
