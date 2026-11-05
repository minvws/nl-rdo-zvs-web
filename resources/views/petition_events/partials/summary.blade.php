@use (App\Enums\PetitionEventType)
@use (App\Enums\RouteName)
<x-app-layout>
    <section class="mb-4">
        <div class="visually-grouped">
            <x-petition-header-details
                :petition="$petition"
                :hasBackLink="true"
                :backLinkRoute="route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $petition->department, 'petition' => $petition])"
                :backLinkLabel="__('general.back_to_petition')" />
        </div>
    </section>

    <x-petition-events.menu
        :petition="$petition"
        :availableTypes="$availableTypes" />

    <section class="mb-4">
        <div class="visually-grouped">
            <x-petition-events.table
                :events="$events"
                :petition="$petition" />
        </div>
    </section>

    <section class="mb-4">
        <div class="visually-grouped">
            <x-objection-events-calendar
                :events="$events"
                :department="$petition->department" />
        </div>
    </section>

    <section>
        <div class="visually-grouped">
            <form
                method="POST"
                action="{{ route(RouteName::PETITION_EVENTS_WIZARD_STORE, ['department' => $petition->department, 'petition' => $petition]) }}">
                @csrf
                <div class="button-container">
                    <button
                        type="submit"
                        class="button secondary">
                        {{ __('term.finish_store') }}
                    </button>
                    <a
                        class="button secondary"
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $petition->department, 'petition' => $petition]) }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-app-layout>
