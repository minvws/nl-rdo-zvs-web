@use(App\Enums\Ability)
@use(App\Enums\PetitionVariant)
@use(App\Enums\RouteName)
@use(Illuminate\Support\Facades\Config)

<div
    class="petition-sidebar"
    hx-boost="true">
    <div
        id="properties-block"
        class="petition-edit"
        data-throw-petition-refresh="header,timeline">
        @include(
            'petition.properties.show',
            [
                'petition' => $petition,
                'petitionTypeConfiguration' => $petitionTypeConfiguration,
                'deadlineNoticeOfDefaultPenaltyPeriodEnd' => $deadlineNoticeOfDefaultPenaltyPeriodEnd,
                'deadlineAppealNotTimelyPenaltyPeriodEnd' => $deadlineAppealNotTimelyPenaltyPeriodEnd,
            ]
        )
    </div>

    <div
        id="assign-primary-block"
        class="petition-edit"
        data-throw-petition-refresh="timeline">
        @include('petition.assign-primary.show', ['petition' => $petition])
    </div>

    <div
        id="assign-secondary-block"
        class="petition-edit"
        data-throw-petition-refresh="timeline">
        @include('petition.assign-secondary.show', ['petition' => $petition])
    </div>

    <div
        id="contact-block"
        class="petition-edit"
        data-throw-petition-refresh="timeline">
        @include('petition.contacts.show', ['petition' => $petition])
    </div>

    @if ($petition->availableCustomDates->isNotEmpty() && ! $petition->isTermEngineConverted())
        <div
            id="custom-dates-block"
            class="petition-edit"
            data-throw-petition-refresh="timeline">
            @include('petition.custom-dates.show', ['petition' => $petition])
        </div>
    @endif

    @if ($petition->availableCustomPetitionProperties->isNotEmpty())
        <div
            id="custom-petition-properties-block"
            class="petition-edit"
            data-throw-petition-refresh="timeline"
            data-grouped-checkboxes="true">
            <x-petition.custom-properties.show :petition="$petition" />
        </div>
    @endif

    @if (Config::array('external_url.' . $petition->petitionType->type->value, []))
        <div
            id="external-urls-block"
            class="petition-edit"
            data-throw-petition-refresh="timeline">
            @include('petition.external-urls.show', ['petition' => $petition])
        </div>
    @endif

    @if (Config::array('querysnapshot.' . $petition->petitionType->type->value, []))
        <div
            id="querysnapshots-block"
            class="petition-edit"
            data-throw-petition-refresh="timeline">
            @include('petition.querysnapshots.show', ['petition' => $petition])
        </div>
    @endif

    <div
        id="policy-department-block"
        class="petition-edit"
        data-throw-petition-refresh="timeline">
        @include('petition.policy-department.show', ['petition' => $petition])
    </div>

    @if ($availableCostTypes->isNotEmpty())
        <div
            id="custom-costs-block"
            class="petition-edit"
            data-throw-petition-refresh="timeline">
            @include('petition.custom-costs.show', ['petition' => $petition])
        </div>
    @endif

    @if ($petition->petitionType->type !== PetitionVariant::WOO_VERZOEK)
        <div
            id="correspondence-block"
            class="petition-edit"
            data-throw-petition-refresh="timeline">
            @include('petition.correspondence.show', ['petition' => $petition])
        </div>
    @endif

    @if ($petition->petitionType->type !== PetitionVariant::BEROEP && ! $petition->isTermEngineConverted())
        <x-petition.petition-terms.summary
            :petition="$petition"
            :petitionTerms="$petition->petitionTerms" />
    @endif

    @can(Ability::UPDATE, $petition)
        <div class="petition-edit">
            <a
                href="{{
                    confirmRoute(
                        route(RouteName::DEPARTMENTS_PETITIONS_ARCHIVE_STORE, ['department' => $department, 'petition' => $petition]),
                        route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $department, 'petition' => $petition]),
                        __('petition.archive_confirmation_text', ['number' => $petition->number]),
                    )
                }}"
                class="button">
                {{ __('petition.archive') }}
            </a>
        </div>
    @endcan
</div>
