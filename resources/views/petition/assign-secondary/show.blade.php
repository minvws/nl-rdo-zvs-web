@use(App\Enums\RouteName)

<div class="petition-property__block">
    <header class="petition-property__header">
        <h2 class="petition-property__title">{{ __('petition.second_assignee') }}</h2>
        @can('update', [$petition])
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_SECONDARY_EDIT, ['department' => $petition->department->slug, 'petition' => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#assign-secondary-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __('petition.second_assignee') }} {{ __('general.edit') }}</span>
            </a>
        @endcan
    </header>
    <div class="petition-property__content">
        @if ($petition->secondAssignee?->user)
            <p>
                {{ $petition->secondAssignee->user->name }}
            </p>
        @else
            <p>{{ '-' }}</p>
        @endif
    </div>
</div>
