@use(App\Enums\ContactType)
@use(App\Enums\RouteName)
@use(App\Facades\ActiveDepartment)
@use(App\Facades\Form)

@section('pageTitle', __('contact.create_in') . ' ' . ActiveDepartment::getActiveDepartment()?->name)

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('contact.create') }}</h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <form
                method="post"
                action="{{ route(RouteName::DEPARTMENTS_CONTACTS_CREATE, ['department' => $department]) }}">
                @csrf
                <fieldset>
                    <legend>{{ __('contact.base') }}</legend>
                    <div class="form-input-group">
                        <x-input-label
                            for="contact-type"
                            :content="__('contact.type')" />
                        <select
                            class="form-select"
                            id="contact-type"
                            name="type">
                            @foreach (ContactType::cases() as $contactType)
                                <option
                                    value="{{ $contactType->value }}"
                                    @selected($contactType->value === Form::old('type'))>
                                    {{ __('contact.contact_type_enum.' . $contactType->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row form-row--two-one-six">
                        <div>
                            <x-input-label
                                for="initials"
                                :content="__('contact.initials')" />
                            <x-input-error
                                id="initials-error"
                                :messages="$errors->get('initials')" />
                            <x-text-input
                                id="initials"
                                :hasError="$errors->has('initials')"
                                type="text"
                                maxlength="20"
                                name="initials"
                                aria-describedby="initials-error"
                                :value="old('initials')" />
                        </div>
                        <div>
                            <x-input-label
                                for="middle_name"
                                :content="__('contact.middle_name')" />
                            <x-input-error
                                id="middle_name-error"
                                :messages="$errors->get('middle_name')" />
                            <x-text-input
                                id="middle_name"
                                :hasError="$errors->has('middle_name')"
                                type="text"
                                name="middle_name"
                                aria-describedby="middle_name-error"
                                :value="old('middle_name')" />
                        </div>
                        <div>
                            <x-input-label
                                for="last_name"
                                :content="__('contact.last_name')" />
                            <x-input-error
                                id="last_name-error"
                                :messages="$errors->get('last_name')" />
                            <x-text-input
                                id="last_name"
                                :hasError="$errors->has('last_name')"
                                type="text"
                                name="last_name"
                                aria-describedby="last_name-error"
                                :value="old('last_name')" />
                        </div>
                    </div>
                    <div>
                        <x-input-label
                            for="organisation_name"
                            :content="__('contact.organisation_name')" />
                        <x-input-error
                            id="organisation_name-error"
                            :messages="$errors->get('organisation_name')" />
                        <x-text-input
                            id="organisation_name"
                            :hasError="$errors->has('organisation_name')"
                            type="text"
                            name="organisation_name"
                            aria-describedby="organisation_name-error"
                            :value="old('organisation_name')" />
                    </div>
                    <div>
                        <x-input-label
                            for="email_address"
                            :content="__('contact.email_address')" />
                        <x-input-error
                            id="email_address-error"
                            :messages="$errors->get('email_address')" />
                        <x-text-input
                            id="email_address"
                            :hasError="$errors->has('email_address')"
                            type="email"
                            name="email_address"
                            aria-describedby="email_address-error"
                            :value="old('email_address')" />
                    </div>
                    <div>
                        <x-input-label
                            for="secondary_email_address"
                            :content="__('contact.secondary_email_address')" />
                        <x-input-error
                            id="secondary_email_address-error"
                            :messages="$errors->get('secondary_email_address')" />
                        <x-text-input
                            id="secondary_email_address"
                            :hasError="$errors->has('secondary_email_address')"
                            type="email"
                            name="secondary_email_address"
                            aria-describedby="secondary_email_address-error"
                            :value="old('secondary_email_address')" />
                    </div>
                    <div>
                        <x-input-label
                            for="phone_number"
                            :content="__('contact.phone_number')" />
                        <x-input-error
                            id="phone_number-error"
                            :messages="$errors->get('phone_number')" />
                        <x-text-input
                            id="phone_number"
                            :hasError="$errors->has('phone_number')"
                            type="tel"
                            maxlength="20"
                            name="phone_number"
                            aria-describedby="phone_number-error"
                            :value="old('phone_number')" />
                    </div>
                    <div class="form-row form-row--three-quarters">
                        <div>
                            <x-input-label
                                for="street"
                                :content="__('contact.street')" />
                            <x-input-error
                                id="street-error"
                                :messages="$errors->get('street')" />
                            <x-text-input
                                id="street"
                                :hasError="$errors->has('street')"
                                type="text"
                                name="street"
                                aria-describedby="street-error"
                                :value="old('street')" />
                        </div>
                        <div>
                            <x-input-label
                                for="house_number"
                                :content="__('contact.house_number')" />
                            <x-input-error
                                id="house_number-error"
                                :messages="$errors->get('house_number')" />
                            <x-text-input
                                id="house_number"
                                :hasError="$errors->has('house_number')"
                                type="text"
                                maxlength="20"
                                name="house_number"
                                aria-describedby="house_number-error"
                                :value="old('house_number')" />
                        </div>
                    </div>
                    <div class="form-row form-row--one-third">
                        <div>
                            <x-input-label
                                for="postal_code"
                                :content="__('contact.postal_code')" />
                            <x-input-error
                                id="postal_code-error"
                                :messages="$errors->get('postal_code')" />
                            <x-text-input
                                id="postal_code"
                                :hasError="$errors->has('postal_code')"
                                type="text"
                                maxlength="20"
                                name="postal_code"
                                aria-describedby="postal_code-error"
                                :value="old('postal_code')" />
                        </div>
                        <div>
                            <x-input-label
                                for="city"
                                :content="__('contact.city')" />
                            <x-input-error
                                id="city-error"
                                :messages="$errors->get('city')" />
                            <x-text-input
                                id="city"
                                :hasError="$errors->has('city')"
                                type="text"
                                name="city"
                                aria-describedby="city-error"
                                :value="old('city')" />
                        </div>
                    </div>
                    <div>
                        <x-input-label
                            for="notes"
                            :content="__('contact.notes')" />
                        <x-input-error
                            id="notes-error"
                            :messages="$errors->get('notes')" />
                        <!-- prettier-ignore -->
                        <textarea
                            class="form-control"
                            id="notes"
                            name="notes">{{ Form::old('notes') }}</textarea>
                    </div>
                </fieldset>
                <fieldset class="mt-4">
                    <legend>{{ __('contact.visiting_address') }}</legend>
                    <div class="form-row form-row--three-quarters">
                        <div>
                            <x-input-label
                                for="visiting_address_street"
                                :content="__('contact.street')" />
                            <x-input-error
                                id="visiting_address_street-error"
                                :messages="$errors->get('visiting_address_street')" />
                            <x-text-input
                                id="visiting_address_street"
                                :hasError="$errors->has('visiting_address_street')"
                                type="text"
                                name="visiting_address_street"
                                aria-describedby="visiting_address_street-error"
                                :value="old('visiting_address_street')" />
                        </div>
                        <div>
                            <x-input-label
                                for="visiting_address_house_number"
                                :content="__('contact.house_number')" />
                            <x-input-error
                                id="visiting_address_house_number-error"
                                :messages="$errors->get('visiting_address_house_number')" />
                            <x-text-input
                                id="visiting_address_house_number"
                                :hasError="$errors->has('visiting_address_house_number')"
                                type="text"
                                maxlength="20"
                                name="visiting_address_house_number"
                                aria-describedby="visiting_address_house_number-error"
                                :value="old('visiting_address_house_number')" />
                        </div>
                    </div>
                    <div class="form-row form-row--one-third">
                        <div>
                            <x-input-label
                                for="visiting_address_postal_code"
                                :content="__('contact.postal_code')" />
                            <x-input-error
                                id="visiting_address_postal_code-error"
                                :messages="$errors->get('visiting_address_postal_code')" />
                            <x-text-input
                                id="visiting_address_postal_code"
                                :hasError="$errors->has('visiting_address_postal_code')"
                                type="text"
                                maxlength="20"
                                name="visiting_address_postal_code"
                                aria-describedby="visiting_address_postal_code-error"
                                :value="old('visiting_address_postal_code')" />
                        </div>
                        <div>
                            <x-input-label
                                for="visiting_address_city"
                                :content="__('contact.city')" />
                            <x-input-error
                                id="visiting_address_city-error"
                                :messages="$errors->get('visiting_address_city')" />
                            <x-text-input
                                id="visiting_address_city"
                                :hasError="$errors->has('visiting_address_city')"
                                type="text"
                                name="visiting_address_city"
                                aria-describedby="visiting_address_city-error"
                                :value="old('visiting_address_city')" />
                        </div>
                    </div>
                </fieldset>
                <fieldset class="mt-4">
                    <legend>{{ __('contact.postal_address') }}</legend>
                    <div class="form-row form-row--three-quarters">
                        <div>
                            <x-input-label
                                for="postal_address_street"
                                :content="__('contact.street')" />
                            <x-input-error
                                id="postal_address_street-error"
                                :messages="$errors->get('postal_address_street')" />
                            <x-text-input
                                id="postal_address_street"
                                :hasError="$errors->has('postal_address_street')"
                                type="text"
                                name="postal_address_street"
                                aria-describedby="postal_address_street-error"
                                :value="old('postal_address_street')" />
                        </div>
                        <div>
                            <x-input-label
                                for="postal_address_house_number"
                                :content="__('contact.house_number')" />
                            <x-input-error
                                id="postal_address_house_number-error"
                                :messages="$errors->get('postal_address_house_number')" />
                            <x-text-input
                                id="postal_address_house_number"
                                :hasError="$errors->has('postal_address_house_number')"
                                type="text"
                                maxlength="20"
                                name="postal_address_house_number"
                                aria-describedby="postal_address_house_number-error"
                                :value="old('postal_address_house_number')" />
                        </div>
                    </div>
                    <div class="form-row form-row--one-third">
                        <div>
                            <x-input-label
                                for="postal_address_postal_code"
                                :content="__('contact.postal_code')" />
                            <x-input-error
                                id="postal_address_postal_code-error"
                                :messages="$errors->get('postal_address_postal_code')" />
                            <x-text-input
                                id="postal_address_postal_code"
                                :hasError="$errors->has('postal_address_postal_code')"
                                type="text"
                                maxlength="20"
                                name="postal_address_postal_code"
                                aria-describedby="postal_address_postal_code-error"
                                :value="old('postal_address_postal_code')" />
                        </div>
                        <div>
                            <x-input-label
                                for="postal_address_city"
                                :content="__('contact.city')" />
                            <x-input-error
                                id="postal_address_city-error"
                                :messages="$errors->get('postal_address_city')" />
                            <x-text-input
                                id="postal_address_city"
                                :hasError="$errors->has('postal_address_city')"
                                type="text"
                                name="postal_address_city"
                                aria-describedby="postal_address_city-error"
                                :value="old('postal_address_city')" />
                        </div>
                    </div>
                </fieldset>
                <fieldset class="mt-4">
                    <legend>{{ __('contact.zivver_legend') }}</legend>
                    <div>
                        <x-input-label
                            for="email_address_2"
                            :content="__('contact.email_address_2')" />
                        <x-input-error
                            id="email_address_2-error"
                            :messages="$errors->get('email_address_2')" />
                        <x-text-input
                            id="email_address_2"
                            :hasError="$errors->has('email_address_2')"
                            type="email"
                            name="email_address_2"
                            aria-describedby="email_address_2-error"
                            :value="old('email_address_2')" />
                    </div>
                    <div>
                        <x-input-label
                            for="email_address_3"
                            :content="__('contact.email_address_3')" />
                        <x-input-error
                            id="email_address_3-error"
                            :messages="$errors->get('email_address_3')" />
                        <x-text-input
                            id="email_address_3"
                            :hasError="$errors->has('email_address_3')"
                            type="email"
                            name="email_address_3"
                            aria-describedby="email_address_3-error"
                            :value="old('email_address_3')" />
                    </div>
                </fieldset>

                <div class="button-container">
                    <x-primary-button>
                        {{ __('contact.create') }}
                    </x-primary-button>
                    <a
                        class="button"
                        href="{{ route(RouteName::DEPARTMENTS_CONTACTS_INDEX, ['department' => $department]) }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-form-layout>
