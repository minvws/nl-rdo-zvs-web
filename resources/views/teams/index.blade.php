@use(App\Enums\Authorization\Permission)
@use(App\Enums\RouteName)

<x-app-layout>
    <x-slot name="header">
        <div class="action-bar">
            <h1>
                {{ __('team.model_plural') }}
            </h1>
            @can(Permission::TEAM_WRITE->value)
                <a
                    class="button"
                    href="{{ route(RouteName::DEPARTMENTS_ADMIN_TEAMS_CREATE, ['department' => $department]) }}">
                    {{ __('team.create') }}
                    <x-tabler-plus
                        aria-hidden="true"
                        focusable="false" />
                </a>
            @endcan
        </div>
    </x-slot>
    <section class="mt-5">
        <div class="visually-grouped">
            <table>
                <caption class="visually-hidden">
                    {{ __('team.model_plural') }}
                </caption>
                <thead>
                    <tr>
                        <th scope="col">{{ __('team.model_singular') }}</th>
                        <th scope="col">{{ __('team.active') }}</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($teams as $team)
                        <tr>
                            <th scope="row">{{ $team->name }}</th>
                            <td>
                                @if ($team->active)
                                    {{ __('general.yes') }}
                                @else
                                    {{ __('general.no') }}
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    @can(Permission::TEAM_WRITE->value)
                                        <a
                                            class="icon-only"
                                            href="{{ route(RouteName::DEPARTMENTS_ADMIN_TEAMS_EDIT, ['department' => $department, 'team' => $team->id]) }}"
                                            aria-label="{{ __('general.view') }}">
                                            <x-tabler-chevron-right
                                                aria-hidden="true"
                                                focusable="false" />
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">{{ __('team.no_teams') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr></tr>
                </tfoot>
            </table>
            {{ $teams->links() }}
        </div>
    </section>
</x-app-layout>
