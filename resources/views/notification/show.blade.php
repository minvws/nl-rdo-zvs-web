@use(App\Enums\RouteName)

@section('pageTitle', Str::title(__('notification.model_singular') . ' ' . __('general.detail')))

<x-app-layout>
    <x-slot name="header">
        <div class="action-bar">
            <h1>{{ Str::title(__('notification.model_singular') . ' ' . __('general.detail')) }}</h1>
        </div>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <div class="mb-3">
                <x-notification-content :notification="$notification" />
            </div>

            <div class>
                <form
                    method="POST"
                    action="{{ route(RouteName::NOTIFICATIONS_MARK_AS_UNREAD, $notification->id) }}"
                    class="flex">
                    @csrf
                    <a
                        class="button"
                        href="{{ route(RouteName::NOTIFICATIONS_INDEX) }}">
                        {{ __('notification.back_to_notifications') }}
                    </a>
                    <button
                        type="submit"
                        class="button button--tertiary">
                        {{ __('notification.back_to_notifications_and_mark_as_unread') }}
                    </button>
                </form>
            </div>
        </div>
    </section>
</x-app-layout>
