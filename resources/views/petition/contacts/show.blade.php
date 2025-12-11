@use(App\Enums\Ability)
@use(App\Enums\RouteName)

<div class="petition-property__block">
    <header class="petition-property__header">
        <h2 class="petition-property__title">{{ __('contact.model_plural') }}</h2>
        @can(\App\Enums\Authorization\Permission::CONTACT_MANAGE)
            @can(Ability::UPDATE, $petition)
                <a
                    href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM, ['department' => $department, 'petition' => $petition]) }}"
                    class="icon-only petition-property__edit">
                    <x-tabler-settings
                        aria-hidden="true"
                        focusable="false" />
                    <span class="visually-hidden">{{ __('contact.model_plural') }} {{ __('general.edit') }}</span>
                </a>
            @endcan
        @endcan
    </header>
    <div class="petition-property__content">
        @if ($petition->contacts->isNotEmpty())
            <x-petition.contact-section
                :contacts="$petition->applicant"
                :title="__('contact.attached_applicant')"
                :petition="$petition" />

            <x-petition.contact-section
                :contacts="$petition->representative"
                :title="__('contact.attached_representative')"
                :petition="$petition" />

            <x-petition.contact-section
                :contacts="$petition->institution"
                :title="__('contact.attached_institution')"
                :petition="$petition" />

            <x-petition.contact-section
                :contacts="$petition->stakeholders"
                :title="__('contact.attached_stakeholders')"
                :petition="$petition" />
        @else
            <p>{{ '-' }}</p>
        @endif
    </div>
</div>
