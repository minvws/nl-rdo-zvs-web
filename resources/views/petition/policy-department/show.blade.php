@use(App\Enums\Ability)
@use(App\Enums\RouteName)

<div class="petition-property__block">
    <header class="petition-property__header">
        <h2 class="petition-property__title">{{ __('policy_department.model_plural') }}</h2>
        @can(Ability::UPDATE, $petition)
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_EDIT, ['department' => $petition->department->slug, 'petition' => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#policy-department-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __('policy_department.edit') }}</span>
            </a>
        @endcan
    </header>
    <div class="petition-property__content">
        @if ($petition->policyDepartments->isNotEmpty())
            {{ $petition->policyDepartments->toString() }}
        @else
            {{ '-' }}
        @endif
    </div>
</div>
