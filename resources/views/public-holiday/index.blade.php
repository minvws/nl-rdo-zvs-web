@use(App\Enums\Authorization\Permission)
@use(App\Enums\RouteName)

@section('pageTitle', __('public_holiday.model_plural'))
<x-app-layout>
    <x-slot name="header">
        <div class="action-bar">
            <h1>
                {{ __('public_holiday.model_plural') }}
            </h1>
            @can(Permission::PUBLIC_HOLIDAY_WRITE)
                <a
                    class="button"
                    href="{{ route(RouteName::ADMIN_PUBLIC_HOLIDAY_CREATE) }}">
                    {{ __('public_holiday.create') }}
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
                    {{ __('public_holiday.model_plural') }}
                </caption>
                <thead>
                    <tr>
                        <th scope="col">{{ __('public_holiday.name') }}</th>
                        <th scope="col">{{ __('public_holiday.date') }}</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($publicHolidays as $publicHoliday)
                        <tr class="table-row-clickable">
                            <th scope="row">{{ $publicHoliday->name }}</th>
                            <td>{{ $publicHoliday->date }}</td>
                            <td>
                                @can(Permission::PUBLIC_HOLIDAY_WRITE)
                                    <a
                                        class="icon-only"
                                        href="{{ route(RouteName::ADMIN_PUBLIC_HOLIDAY_EDIT, ['publicHoliday' => $publicHoliday]) }}"
                                        aria-label="{{ __('general.view') }}">
                                        <x-tabler-chevron-right
                                            aria-hidden="true"
                                            focusable="false" />
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">{{ __('public_holiday.no_holidays') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr></tr>
                </tfoot>
            </table>
            {{ $publicHolidays->links() }}
        </div>
    </section>
</x-app-layout>
