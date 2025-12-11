@section('pageTitle', __('user.model_plural'))
<x-app-layout>
    <x-slot name="header">
        <div class="action-bar">
            <h1>{{ __('user.model_plural') }}</h1>
            @can(Permission::USER_WRITE->value)
                <a
                    class="button"
                    href="{{ route(RouteName::ADMIN_USER_CREATE) }}">
                    {{ __('general.create') }}
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
                <thead>
                    <tr>
                        <th scope="col">Naam</th>
                        <th scope="col">E-mailadres</th>
                        <th scope="col">{{ __('user.active') }}</th>
                        <th scope="col">&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="table-row-clickable">
                            <th scope="row">{{ $user->name }}</th>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->active ? __('general.yes') : __('general.no') }}</td>
                            <td class="text-end">
                                <a
                                    class="icon-only"
                                    href="{{ route(RouteName::ADMIN_USER_EDIT, ['user' => $user]) }}"
                                    aria-label="{{ __('general.edit') }}">
                                    <x-tabler-chevron-right
                                        aria-hidden="true"
                                        focusable="false" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">{{ __('user.no_users') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr></tr>
                </tfoot>
            </table>
            {{ $users->links() }}
        </div>
    </section>
</x-app-layout>
