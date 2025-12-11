@section('pageTitle', __('petition_deliverable.create', ['petition_deliverable_type' => __(sprintf('petition_deliverable.petition_deliverable_type.%s', $petitionDeliverableType->value))]))

<x-form-layout>
    <x-slot name="header">
        <h1>
            {{ __('petition_deliverable.create', ['petition_deliverable_type' => __(sprintf('petition_deliverable.petition_deliverable_type.%s', $petitionDeliverableType->value))]) }}
        </h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <form
                method="post"
                action="{{
                    route(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_STORE, [
                        'department' => $petition->department->slug,
                        'petition' => $petition,
                        'petitionDeliverableType' => $petitionDeliverableType,
                    ])
                }}">
                @csrf

                <div class="form-input-group">
                    <x-input-label
                        for="deadline_at"
                        required
                        :content="__('petition_deliverable.deadline_at')" />
                    <x-input-error
                        id="date-error"
                        :messages="$errors->get('deadline_at')" />
                    <input
                        class="form-control @error('deadline_at') input-error @enderror"
                        id="deadline_at"
                        aria-describedby="deadline_at-error"
                        type="date"
                        name="deadline_at"
                        value="{{ Form::old('deadline_at') }}" />
                </div>

                <div>
                    <x-input-label
                        for="amount"
                        :content="__('petition_deliverable.description')" />
                    <x-input-error
                        id="description-error"
                        :messages="$errors->get('description')" />
                    <x-text-input
                        id="description"
                        :hasError="$errors->has('description')"
                        type="text"
                        name="description"
                        aria-describedby="description-error"
                        value="{{ Form::old('description') }}" />
                </div>

                <div class="button-container">
                    <x-primary-button>
                        {{ __('petition_deliverable.create', ['petition_deliverable_type' => __(sprintf('petition_deliverable.petition_deliverable_type.%s', $petitionDeliverableType->value))]) }}
                    </x-primary-button>
                    <a
                        class="button"
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $petition->department->slug, 'petition' => $petition]) }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-form-layout>
