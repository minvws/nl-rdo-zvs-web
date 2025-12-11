@use(App\Enums\Authorization\Permission)
@use(App\Enums\RouteName)

@can('update', [$petition])
    <div class="button-container button-container--wrap">
        @foreach ($termTypes as $termType)
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_TERMS_CREATE, ['department' => $petition->department, 'petition' => $petition, 'termType' => $termType]) }}">
                {{ __('term.term_type.' . $termType->value) }}
            </a>
        @endforeach

        @if ($draftTermButton)
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_CREATE, ['department' => $petition->department, 'petition' => $petition]) }}">
                {{ __('draft_term.create') }}
            </a>
        @endif

        @if (Config::get('app.features.term_engine_v2'))
            <a
                href="{{ route(RouteName::PETITION_EVENTS_WIZARD_RESET, ['department' => $petition->department, 'petition' => $petition]) }}">
                <strong>{{ __('term.manage_terms') }} v2</strong>
            </a>
        @endif
    </div>
@endcan
