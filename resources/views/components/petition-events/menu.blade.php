@use(App\Enums\RouteName)
<section class="mb-4">
    <div class="visually-grouped">
        {{-- Options for next step or finish --}}
        @if (count($availableTypes ?? []) > 0)
            <div id="petition-events-buttons">
                <h2>{{ __('term.add_term') }}</h2>
                <div id="petition-events-menu">
                    @foreach ($availableTypes as $type)
                        <div class="petition-events-menu-card">
                            <p>
                                <a
                                    class="button"
                                    href="{{ route(RouteName::PETITION_EVENTS_WIZARD_CREATE, ['department' => $petition->department, 'petition' => $petition, 'type' => $type->value]) }}">
                                    {{ $type->label($petition->petitionType->type) }}
                                </a>
                            </p>
                            <p class="mt-2">
                                {{ $type->description($petition->petitionType->type) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <h2>{{ __('term.add_term') }}</h2>
            <p>{{ __('term.no_more_terms_can_be_added') }}</p>
        @endif
    </div>
</section>
