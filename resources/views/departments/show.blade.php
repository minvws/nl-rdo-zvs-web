@section('pageTitle', __('department.select_department'))

@ifNotHtmx
    <h1>{{ __('department.select_department') }}</h1>
@endifNotHtmx

@foreach ($departments as $department)
    <a
        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department]) }}"
        class="department-selector__link {{ $activeDepartment?->slug === $department->slug ? 'current' : '' }}">
        <span class="department-selector__tag department-selector__tag--{{ $department->slug }}">
            {{ Str::upper($department->abbreviation) }}
        </span>
        {{ $department->name }}
    </a>
@endforeach
