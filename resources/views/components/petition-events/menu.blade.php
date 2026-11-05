@use (App\Enums\RouteName)
<section class="mb-4">
    <div class="visually-grouped">
        {{-- Options for next step or finish --}}
        @if (count($availableTypes ?? []) > 0)
            <div id="petition-events-buttons">
                <h2>{{ __('term.add_term') }}</h2>
                <ul
                    id="petition-events-menu"
                    class="tiles">
                    @foreach ($availableTypes as $type)
                        <li class="tile">
                            <a
                                href="{{ route(RouteName::PETITION_EVENTS_WIZARD_CREATE, ['department' => $petition->department, 'petition' => $petition, 'type' => $type->value]) }}">
                                {{ $type->label($petition->petitionType->type) }}
                                <x-tabler-plus
                                    aria-hidden="true"
                                    focusable="false" />
                            </a>
                            <div class="tile-group">
                                <p>{{ $type->description($petition->petitionType->type) }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            <h2>{{ __('term.add_term') }}</h2>
            <p>{{ __('term.no_more_terms_can_be_added') }}</p>
        @endif
    </div>
</section>
