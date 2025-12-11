@use(App\Enums\NotificationFilter)
@use(App\Enums\RouteName)
@use(Illuminate\Support\Str)

@section('pageTitle', Str::title(__('notification.model_plural')))

<x-app-layout>
    <x-slot name="header">
        <div class="action-bar">
            <h1>{{ Str::title(__('notification.model_plural')) }}</h1>
        </div>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped visually-grouped--horizontal filters">
            <form
                data-auto-submit="form"
                method="get"
                action="{{ route(RouteName::NOTIFICATIONS_INDEX) }}">
                <div
                    class="form-filter-group form-filter-group--horizontal"
                    data-filter-group="status">
                    <x-input-label
                        class="form-label"
                        for="filter_status"
                        :content="__('general.filter')" />
                    <select
                        data-auto-submit="input"
                        id="filter_status"
                        name="filter"
                        class="form-select">
                        @foreach ($notificationFilters as $notificationFilter)
                            <option
                                value="{{ $notificationFilter->value }}"
                                @selected($filter === $notificationFilter->value || (! request()->has('filter') && $notificationFilter->value === 'all'))>
                                {{ $notificationFilter->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
            @if ($unreadCount > 0)
                <form
                    method="POST"
                    action="{{ route(RouteName::NOTIFICATIONS_MARK_ALL_READ) }}">
                    @csrf
                    <button
                        type="submit"
                        class="button">
                        {{ __('notification.mark_all_read') }}
                    </button>
                </form>
            @endif
        </div>
    </section>

    <section class="mt-5">
        <div class="visually-grouped">
            <div class="x-scrollable-wrapper">
                <div class="shadow shadow-left"></div>
                <div class="shadow shadow-right"></div>
                <div class="x-scrollable">
                    <table>
                        <caption class="visually-hidden">{{ __('notification.overview') }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">{{ __('notification.message') }}</th>
                                <th
                                    scope="col"
                                    class="align-right">
                                    {{ __('general.date-time') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($notifications as $notification)
                                <tr class="{{ is_null($notification->read_at) ? 'fw-bold' : '' }}">
                                    <td>
                                        <a href="{{ route(RouteName::NOTIFICATIONS_SHOW, $notification->id) }}">
                                            {{ $notification->data['title'] ?? Str::title(__('notification.model_singular')) }}
                                        </a>
                                    </td>
                                    <td>{{ $notification->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2">{{ __('notification.no_notifications_found') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            {{ $notifications->links() }}
        </div>
    </section>
</x-app-layout>
