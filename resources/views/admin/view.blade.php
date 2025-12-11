@use(App\Enums\Authorization\Permission)
@use(App\Enums\RouteName)

@section('pageTitle', __('general.administration'))
<x-form-layout>
    <section class="mt-5">
        <h2>{{ __('general.global') }}</h2>
        <div class="section-container mt-3">
            @can(Permission::USER_WRITE->value)
                <div>
                    <h3 class="department-overview__title">{{ __('general.access') }}</h3>
                    <a
                        class="button mt-2"
                        href="{{ route(RouteName::ADMIN_USER_INDEX) }}">
                        {{ __('user.model_plural') }}
                    </a>
                </div>
            @endcan

            @can(Permission::POLICY_DEPARTMENT_WRITE->value)
                <div>
                    <h3 class="department-overview__title">{{ __('general.organization') }}</h3>
                    <a
                        class="button mt-2"
                        href="{{ route(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX) }}">
                        {{ __('policy_department.model_plural') }}
                    </a>
                </div>
            @endcan

            @can(Permission::PUBLIC_HOLIDAY_WRITE->value)
                <div>
                    <h3 class="department-overview__title">{{ __('general.general_term_law') }}</h3>
                    <a
                        class="button mt-2"
                        href="{{ route(RouteName::ADMIN_PUBLIC_HOLIDAY_INDEX) }}">
                        {{ __('public_holiday.model_plural') }}
                    </a>
                </div>
            @endcan
        </div>
    </section>

    <section class="mt-5">
        <h2>{{ __('department.model_plural') }}</h2>
        <div class="section-container mt-3">
            @foreach ($departments as $department)
                <div>
                    <h3 class="department-overview__title">
                        <span
                            class="department-selector__tag department-selector__tag--{{ $department->slug }}"
                            aria-hidden="true"></span>
                        {{ $department->name }}
                    </h3>
                    @can(Permission::PETITION_TYPE_WRITE->value)
                        <a
                            class="button mt-2"
                            href="{{
                                route(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_INDEX, [
                                    'department' => $department,
                                ])
                            }}">
                            {{ __('petition_type.model_plural') }}
                        </a>
                    @endcan

                    @can(Permission::PETITION_CATEGORY_WRITE->value)
                        <a
                            class="button mt-2"
                            href="{{
                                route(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_INDEX, [
                                    'department' => $department,
                                ])
                            }}">
                            {{ __('petition_category.model_plural') }}
                        </a>
                    @endcan
                </div>
            @endforeach
        </div>
    </section>
</x-form-layout>
