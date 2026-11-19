@use (App\Enums\RouteName)
@use (App\Services\NotificationRule\NotificationRuleText)

<x-app-layout>
    <x-slot name="header">
        <div class="action-bar">
            <h1>{{ __('notification_rule.model_plural') }}</h1>
            <a
                class="button"
                href="{{ route(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_CREATE, ['department' => $department]) }}">
                {{ __('notification_rule.create') }}
                <x-tabler-plus
                    aria-hidden="true"
                    focusable="false" />
            </a>
        </div>
    </x-slot>
    <section class="mt-5">
        <div class="visually-grouped">
            <table>
                <caption class="visually-hidden">
                    {{ __('notification_rule.model_plural') }}
                </caption>
                <thead>
                    <tr>
                        <th scope="col">{{ __('notification_rule.event_type') }}</th>
                        <th scope="col">{{ __('notification_rule.anchor') }}</th>
                        <th scope="col">{{ __('notification_rule.days_label') }}</th>
                        <th scope="col">{{ __('notification_rule.timing') }}</th>
                        <th scope="col">{{ __('notification_rule.active') }}</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($notificationRules as $notificationRule)
                        <tr>
                            <th scope="row">
                                {{ $eventTypes[$notificationRule->event_type->value] ?? NotificationRuleText::regarding($notificationRule) }}
                            </th>
                            <td>{{ __('notification_rule.anchors.' . $notificationRule->anchor->value) }}</td>
                            <td>{{ NotificationRuleText::term($notificationRule) }}</td>
                            <td>{{ __('notification_rule.timings.' . $notificationRule->timing()->value) }}</td>
                            <td>
                                @if ($notificationRule->active)
                                    {{ __('general.yes') }}
                                @else
                                    {{ __('general.no') }}
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    <a
                                        class="icon-only"
                                        href="{{ route(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_EDIT, ['department' => $department, 'notificationRule' => $notificationRule->id]) }}"
                                        aria-label="{{ __('notification_rule.edit') }}">
                                        <x-tabler-chevron-right
                                            aria-hidden="true"
                                            focusable="false" />
                                    </a>
                                    <a
                                        class="icon-only"
                                        href="{{
                                            confirmRoute(
                                                route(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_DELETE, ['department' => $department, 'notificationRule' => $notificationRule->id]),
                                                route(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_INDEX, ['department' => $department]),
                                                __('notification_rule.delete_confirmation_text'),
                                                'DELETE',
                                            )
                                        }}"
                                        aria-label="{{ __('notification_rule.delete') }}">
                                        <x-tabler-trash
                                            aria-hidden="true"
                                            focusable="false" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">{{ __('notification_rule.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr></tr>
                </tfoot>
            </table>
            {{ $notificationRules->links() }}
        </div>
    </section>
</x-app-layout>
