<x-app-layout>
    <x-slot name="header">
        <div class="action-bar">
            <h1>
                {{ __('petition_type.model_plural') }}
            </h1>
            @can(Permission::PETITION_TYPE_WRITE->value)
                <a
                    class="button"
                    href="{{ route(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_CREATE, ['department' => $department]) }}">
                    {{ __('petition_type.create') }}
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
                    {{ __('petition_type.model_plural') }}
                </caption>
                <thead>
                    <tr>
                        <th scope="col">{{ __('petition_type.name') }}</th>
                        <th scope="col">{{ __('petition_type.active') }}</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($petitionTypes as $petitionType)
                        <tr class="table-row-clickable">
                            <th scope="row">{{ $petitionType->name }}</th>
                            <td>{{ $petitionType->active ? __('general.yes') : __('general.no') }}</td>
                            <td>
                                @can(Permission::PETITION_TYPE_WRITE->value)
                                    <a
                                        class="icon-only"
                                        href="{{ route(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_EDIT, ['department' => $department, 'petitionType' => $petitionType->id]) }}"
                                        aria-label="{{ __('general.view') }}">
                                        >
                                        <x-tabler-chevron-right
                                            aria-hidden="true"
                                            focusable="false" />
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">{{ __('petition_type.no_types') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr></tr>
                </tfoot>
            </table>
            {{ $petitionTypes->links() }}
        </div>
    </section>
</x-app-layout>
