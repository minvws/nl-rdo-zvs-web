@use(App\Enums\RouteName)

@section('pageTitle', __('policy_department.edit'))

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('policy_department.edit') }}</h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <form
                method="post"
                action="{{ route(RouteName::ADMIN_POLICY_DEPARTMENT_UPDATE, ['policyDepartment' => $policyDepartment]) }}">
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
                        maxlength="255"
                        id="name"
                        :hasError="$errors->has('name')"
                        type="text"
                        name="name"
                        aria-describedby="name-error"
                        value="{{ Form::old('name', $policyDepartment->name) }}" />
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
                        @checked(old('active', (string) ($policyDepartment->active ? '1' : '0')) === '1') />
                    <x-input-label
                        for="active"
                        :content="__('policy_department.active')" />
                </div>
                <div class="button-container">
                    <x-primary-button>
                        {{ __('policy_department.edit') }}
                    </x-primary-button>
                    <a
                        class="button"
                        href="{{ route(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX) }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-form-layout>
