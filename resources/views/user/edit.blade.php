@section('pageTitle', $user->name . ' ' . __('user.edit'))
<x-form-layout>
    <x-slot name="header">
        <h1>
            {{ $user->name }}
        </h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <form
                method="post"
                action="{{ route(RouteName::ADMIN_USER_UPDATE, ['user' => $user]) }}">
                @csrf
                <fieldset>
                    <legend>{{ __('user.model_singular') }}</legend>
                    <div>
                        <x-input-label
                            for="name"
                            required
                            :content="__('user.name')" />
                        <x-input-error
                            id="name-error"
                            :messages="$errors->get('name')" />
                        <x-text-input
                            maxlength="128"
                            id="name"
                            :hasError="$errors->has('name')"
                            type="text"
                            name="name"
                            aria-describedby="name-error"
                            value="{{ Form::old('name', $user->name) }}" />
                    </div>
                    <div>
                        <x-input-label
                            for="email"
                            required
                            :content="__('user.email')" />
                        <x-input-error
                            id="email-error"
                            :messages="$errors->get('email')" />
                        <x-text-input
                            maxlength="128"
                            id="email"
                            :hasError="$errors->has('email')"
                            type="email"
                            name="email"
                            aria-describedby="email-error"
                            value="{{ Form::old('email', $user->email) }}" />
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
                            @checked(old('active', (string) ($user->active ? '1' : '0')) === '1') />
                        <x-input-label
                            for="active"
                            :content="__('user.active')" />
                    </div>
                </fieldset>
                <fieldset>
                    <legend>{{ __('role.global') }}</legend>
                    @foreach ($globalRoles as $globalRole)
                        <div class="checkbox">
                            <input
                                type="checkbox"
                                id="global_role-{{ $globalRole->value }}"
                                name="global_roles[]"
                                value="{{ $globalRole->value }}"
                                @checked($errors->any() ? is_array(Form::oldArray('global_roles')) && in_array($globalRole->value, Form::oldArray('global_roles')) : in_array($globalRole, $userGlobalRoles)) />
                            <x-input-label
                                for="global_role-{{ $globalRole->value }}"
                                :content="__('role.global_roles.'.$globalRole->value)" />
                        </div>
                    @endforeach
                </fieldset>
                <fieldset>
                    @foreach ($departments as $department)
                        <legend>{{ $department->name }}</legend>
                        @foreach ($departmentRoles as $departmentRole)
                            <div class="checkbox">
                                <input
                                    type="checkbox"
                                    id="department_role-{{ $department->id }}-{{ $departmentRole->value }}"
                                    name="department_roles[{{ $department->id }}][]"
                                    value="{{ $departmentRole->value }}"
                                    @checked($errors->any() ? is_array(Form::oldArray('department_roles')) && array_key_exists($department->id->toString(), Form::oldArray('department_roles')) && in_array($departmentRole->value, Form::oldArray('department_roles')[$department->id->toString()]) : array_key_exists($department->id->toString(), $userDepartmentRoles) && in_array($departmentRole, $userDepartmentRoles[$department->id->toString()])) />
                                <x-input-label
                                    for="department_role-{{ $department->id }}-{{ $departmentRole->value }}"
                                    :content="__('role.department_roles.'.$departmentRole->value)" />
                            </div>
                        @endforeach
                    @endforeach
                </fieldset>
                <div class="button-container">
                    <x-primary-button>
                        {{ __('general.save') }}
                    </x-primary-button>
                    <a
                        class="button"
                        href="{{ route(RouteName::ADMIN_USER_INDEX) }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>

            <form
                class="mt-5"
                method="post"
                action="{{ route(RouteName::ADMIN_USER_OTP_RESET, ['user' => $user]) }}">
                @csrf
                <fieldset>
                    <legend>{{ __('user.otp_reset') }}</legend>
                    <x-primary-button>
                        {{ __('user.otp_reset') }}
                    </x-primary-button>
                </fieldset>
            </form>
        </div>
    </section>
</x-form-layout>
