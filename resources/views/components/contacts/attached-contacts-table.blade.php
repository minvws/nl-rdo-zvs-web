@use(App\Enums\ContactRole)
@use(App\Enums\RouteName)
@use(App\Enums\CorrespondencePreference)

@props([
    'title',
    'contacts',
    'contactRole',
    'petition',
    'emptyMessage',
])

@php
    $showFirstPhoneContact = $contactRole === ContactRole::APPLICANT;
@endphp

<section class="mt-5">
    <div class="visually-grouped">
        <h2>{{ $title }}</h2>
        <table
            class="connected-contacts-table @if ($showFirstPhoneContact) connected-contacts-table--with-phone-contact @endif">
            <thead>
                <tr>
                    <th scope="col">{{ __('contact.last_name') }}</th>
                    <th scope="col">{{ __('contact.organisation_name') }}</th>
                    <th scope="col">{{ __('contact.correspondence') }}</th>
                    @if ($showFirstPhoneContact)
                        <th scope="col">{{ __('contact.date_first_phone_contact_short') }}</th>
                    @endif

                    <th scope="col">
                        <span class="visually-hidden">{{ __('general.save') }}</span>
                    </th>
                    <th scope="col">
                        <span class="visually-hidden">{{ __('general.actions') }}</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @if ($contacts->isNotEmpty())
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
                            <td>
                                <form
                                    id="contact-pivot-form-{{ $contact->id }}"
                                    class=".contact-preferences-form flex"
                                    action="{{
                                        route(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_UPDATE_PIVOT, [
                                            'department' => $petition->department,
                                            'petition' => $petition,
                                            'contact' => $contact,
                                        ])
                                    }}"
                                    method="POST">
                                    @csrf
                                    <x-text-input
                                        name="reference"
                                        type="text"
                                        maxlength="128"
                                        value="{{ $contact->pivot->reference ?? '' }}"
                                        placeholder="{{ __('contact.reference') }}" />
                                    <select
                                        name="correspondence_preference"
                                        class="form-select">
                                        @foreach (CorrespondencePreference::cases() as $preference)
                                            <option
                                                value="{{ $preference->value }}"
                                                @selected($contact->pivot->correspondence_preference?->value === $preference->value)>
                                                {{ __('contact.correspondence_preference_enum.' . $preference->value) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            @if ($showFirstPhoneContact)
                                <td>
                                    <label
                                        class="visually-hidden"
                                        for="date-first-phone-contact-{{ $contact->id }}">
                                        {{ __('contact.date_first_phone_contact') }}
                                    </label>
                                    <input
                                        class="form-control @error('date_first_phone_contact') input-error @enderror"
                                        type="date"
                                        id="date-first-phone-contact-{{ $contact->id }}"
                                        form="contact-pivot-form-{{ $contact->id }}"
                                        name="date_first_phone_contact"
                                        value="{{ old('date_first_phone_contact', $contact->pivot->date_first_phone_contact) }}" />
                                </td>
                            @endif

                            <td>
                                <x-primary-button form="contact-pivot-form-{{ $contact->id }}">
                                    {{ __('general.save') }}
                                </x-primary-button>
                            </td>

                            <td>
                                <form
                                    action="{{
                                        route(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_DETACH, [
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
                                        value="{{ $contactRole->value }}" />
                                    <x-secondary-button>
                                        {{ __('general.detach') }}
                                    </x-secondary-button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="{{ $showFirstPhoneContact ? 6 : 5 }}">{{ $emptyMessage }}</td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
                <tr></tr>
            </tfoot>
        </table>
    </div>
</section>
