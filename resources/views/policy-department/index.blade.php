@use(App\Enums\Authorization\Permission)
@use(App\Enums\RouteName)

<x-app-layout>
    <x-slot name="header">
        <div class="action-bar">
            <h1>
                {{ __('policy_department.model_plural') }}
            </h1>
            @can(Permission::POLICY_DEPARTMENT_WRITE)
                <a
                    class="button"
                    href="{{ route(RouteName::ADMIN_POLICY_DEPARTMENT_CREATE) }}">
                    {{ __('policy_department.create') }}
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
                    {{ __('policy_department.model_plural') }}
                </caption>
                <thead>
                    <tr>
                        <th scope="col">{{ __('policy_department.model_singular') }}</th>
                        <th scope="col">{{ __('policy_department.active') }}</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($policyDepartments as $policyDepartment)
                        <tr>
                            <th scope="row">{{ $policyDepartment->name }}</th>
                            <td>
                                @if ($policyDepartment->active)
                                    {{ __('general.yes') }}
                                @else
                                    {{ __('general.no') }}
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    @can(Permission::POLICY_DEPARTMENT_WRITE)
                                        <a
                                            class="icon-only"
                                            href="{{ route(RouteName::ADMIN_POLICY_DEPARTMENT_EDIT, ['policyDepartment' => $policyDepartment]) }}"
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
                            <td colspan="3">{{ __('policy_department.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr></tr>
                </tfoot>
            </table>
            {{ $policyDepartments->links() }}
        </div>
    </section>
</x-app-layout>
