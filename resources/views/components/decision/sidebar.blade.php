@use(App\Enums\Ability)
@use(App\Enums\RouteName)

<div
    class="decision-sidebar"
    hx-boost="true">
    <div
        id="properties-block"
        class="decision-edit"
        data-throw-petition-refresh="header">
        @include('decision.properties.show', ['decision' => $decision])
    </div>

    @can(Ability::UPDATE, $decision)
        @if ($decision->archived_at === null)
            <div class="decision-edit">
                <a
                    href="{{
                        confirmRoute(
                            route(RouteName::DEPARTMENTS_DECISIONS_ARCHIVE_STORE, ['department' => $decision->department, 'decision' => $decision]),
                            route(RouteName::DEPARTMENTS_DECISIONS_SHOW, ['department' => $decision->department, 'decision' => $decision]),
                            __('decision.archive_confirmation_text', ['name' => $decision->name]),
                        )
                    }}"
                    class="button mt-5">
                    {{ __('decision.archive') }}
                </a>
            </div>
        @endif
    @endcan

    @can(Ability::UNARCHIVE, $decision)
        @if ($decision->archived_at !== null)
            <div class="decision-edit">
                <a
                    href="{{
                        confirmRoute(
                            route(RouteName::DEPARTMENTS_DECISIONS_UNARCHIVE_STORE, ['department' => $decision->department, 'decision' => $decision]),
                            route(RouteName::DEPARTMENTS_DECISIONS_SHOW, ['department' => $decision->department, 'decision' => $decision]),
                            __('decision.unarchive_confirmation_text', ['name' => $decision->name]),
                        )
                    }}"
                    class="button mt-5">
                    {{ __('decision.unarchive') }}
                </a>
            </div>
        @endif
    @endcan
</div>
