@section('pageTitle', __('petition_category.edit'))

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('petition_category.edit') }}</h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <form
                method="post"
                action="{{ route(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_EDIT, ['department' => $department, 'petitionCategory' => $petitionCategory->id]) }}">
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
                        value="{{ Form::old('name', $petitionCategory->name) }}" />
                </div>
                <div class="checkbox">
                    <input
                        type="hidden"
                        name="active"
                        value="0" />
                    <input
                        id="active"
                        type="checkbox"
                        name="active"
                        value="1"
                        @checked(old('active', (string) ($petitionCategory->active ? '1' : '0')) === '1') />
                    <x-input-label
                        for="active"
                        :content="__('petition_category.active')" />
                </div>
                <div class="button-container">
                    <x-primary-button>
                        {{ __('petition_category.edit') }}
                    </x-primary-button>
                    <a
                        class="button"
                        href="{{ route(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_INDEX, ['department' => $department]) }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-form-layout>
