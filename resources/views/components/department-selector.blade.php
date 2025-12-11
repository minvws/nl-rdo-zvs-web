<div class="department-selector">
    @if ($hasMultipleDepartments)
        <a
            id="toggle-element"
            class="department-selector__trigger"
            href="{{ route(RouteName::DEPARTMENTS_SHOW) }}"
            aria-expanded="false"
            aria-controls="department-selector__list"
            hx-boost="true"
            hx-push-url="false"
            hx-swap="innerHTML"
            hx-target="#department-selector__list">
            <span class="department-selector__tag department-selector__tag--{{ $activeDepartment->slug }}">
                {{ Str::upper($activeDepartment->abbreviation) }}
            </span>
            {{ $activeDepartment->name }}
            <x-tabler-chevron-down
                class="icon"
                aria-hidden="true"
                focusable="false" />
        </a>
        <div
            id="department-selector__list"
            class="department-selector__list"
            hidden></div>
    @else
        <p class="department-selector__name">
            <span class="department-selector__tag department-selector__tag--{{ $activeDepartment->slug }}">
                {{ Str::upper($activeDepartment->abbreviation) }}
            </span>
            {{ $activeDepartment->name }}
        </p>
    @endif
</div>
