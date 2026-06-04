@use(App\Enums\RouteName)

<div class="petition-property__block">
    <header class="petition-property__header">
        <h2 class="petition-property__title">{{ __('petition.assigned_user') }}</h2>
        @can('update', [$petition])
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_EDIT, ['department' => $petition->department->slug, 'petition' => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#assign-primary-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __('petition.assigned_user') }} {{ __('general.edit') }}</span>
            </a>
        @endcan
    </header>
    <div class="petition-property__content">
        @if ($petition->firstAssignee?->user)
            <p>
                {{ $petition->firstAssignee->user->name }}
            </p>
        @else
            <p>{{ '-' }}</p>
        @endif
    </div>
</div>
