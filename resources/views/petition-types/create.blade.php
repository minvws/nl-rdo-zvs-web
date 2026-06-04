@use(App\Enums\PetitionVariant)
@use(App\Enums\RouteName)
@use(App\Facades\Form)

@section('pageTitle', __('petition_type.create'))

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('petition_type.create') }}</h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <form
                method="post"
                action="{{ route(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_CREATE, ['department' => $department]) }}">
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
                        maxlength="64"
                        id="name"
                        :hasError="$errors->has('name')"
                        type="text"
                        name="name"
                        aria-describedby="name-error"
                        :value="old('name')" />
                </div>
                <x-input-label
                    for="type"
                    :content="__('petition_type.petition-type-type')" />
                <select
                    class="form-select"
                    id="petition-type-type"
                    name="type">
                    @foreach (PetitionVariant::cases() as $type)
                        <option
                            value="{{ $type->value }}"
                            @selected($type->value === Form::old('type'))>
                            {{ __('petition_type.' . $type->value) }}
                        </option>
                    @endforeach
                </select>
                <div>
                    <x-input-label
                        for="particularity_label"
                        :content="__('petition_type.particularity_label')" />
                    <x-input-error
                        id="particularity_label-error"
                        :messages="$errors->get('particularity_label')" />
                    <x-text-input
                        maxlength="16"
                        id="particularity_label"
                        :hasError="$errors->has('particularity_label')"
                        type="text"
                        name="particularity_label"
                        aria-describedby="particularity_label-error"
                        :value="old('particularity_label')" />
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
                        @checked(old('active', true)) />
                    <label for="active">{{ __('petition_type.active') }}</label>
                </div>
                <div class="button-container">
                    <x-primary-button>
                        {{ __('petition_type.create') }}
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
