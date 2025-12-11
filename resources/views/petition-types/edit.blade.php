@use(App\Facades\Form)
@use(App\Enums\RouteName)

@section('pageTitle', __('petition_type.edit'))

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('petition_type.edit') }}</h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <form
                method="post"
                action="{{ route(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_EDIT, ['department' => $department, 'petitionType' => $petitionType->id]) }}">
                @csrf
                <div>
                    <x-input-label
                        for="name"
                        required
                        :content="__('general.name')" />
                    <x-input-error
                        id="name-error"
                        :messages="$errors->get('name')" />
                    <x-text-input
                        id="name"
                        :hasError="$errors->has('name')"
                        type="text"
                        name="name"
                        aria-describedby="name-error"
                        value="{{ Form::old('name', $petitionType->name) }}" />
                </div>

                <div>
                    <x-input-label
                        for="type"
                        :content="__('petition_type.petition-type-type')" />
                    <x-text-input
                        disabled
                        type="text"
                        name="petition-type-type"
                        aria-describedby="petition-type-type-error"
                        value="{{ __(sprintf('petition_type.%s', $petitionType->type->value)) }}" />
                </div>

                <div>
                    <x-input-label
                        for="particularity_label"
                        :content="__('petition_type.particularity_label')" />
                    <x-input-error
                        id="particularity_label-error"
                        :messages="$errors->get('particularity_label')" />
                    <x-text-input
                        id="particularity_label"
                        :hasError="$errors->has('particularity_label')"
                        type="text"
                        name="particularity_label"
                        aria-describedby="particularity_label-error"
                        value="{{ Form::old('particularity_label', $petitionType->particularity_label) }}" />
                </div>

                <div>
                    <input
                        type="hidden"
                        name="active"
                        value="0" />
                    <input
                        type="checkbox"
                        id="active"
                        name="active"
                        value="1"
                        @checked(Form::old('active', $petitionType->active)) />
                    <label for="active">{{ __('petition_type.active') }}</label>
                </div>

                <div class="button-container">
                    <x-primary-button>
                        {{ __('petition_type.edit') }}
                    </x-primary-button>
                    <a
                        class="button"
                        href="{{ route(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_INDEX, ['department' => $department]) }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-form-layout>
