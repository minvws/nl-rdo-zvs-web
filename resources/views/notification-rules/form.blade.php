@use (App\Enums\NotificationRuleAnchor)
@use (App\Enums\NotificationRuleTiming)
@use (App\Enums\PetitionEventType)
@use (App\Enums\RouteName)
@use (App\Services\NotificationRule\NotificationRuleAnchorResolver)

@php
    $selectedEventType = old('event_type', request()->query('event_type', $notificationRule?->event_type->value));
    $selectedAnchor = old('anchor', request()->query('anchor', $notificationRule?->anchor->value));
    $selectedTiming = old('timing', $notificationRule?->timing()->value);
    $eventTypeHasEnd = NotificationRuleAnchorResolver::hasEndDate(PetitionEventType::tryFrom((string) $selectedEventType));
@endphp

<section class="mt-5">
    <div class="visually-grouped">
        <form
            class="notification-rule-form"
            method="post"
            action="{{ $action }}">
            @csrf
            <div>
                <x-input-label
                    for="event_type"
                    required
                    :content="__('notification_rule.event_type')" />
                <p id="event_type-hint">{{ __('notification_rule.event_type_hint') }}</p>
                <x-input-error
                    id="event_type-error"
                    :messages="$errors->get('event_type')" />
                <select
                    class="form-select @error('event_type') input-error @enderror"
                    id="event_type"
                    name="event_type"
                    aria-describedby="event_type-hint event_type-error"
                    hx-get="{{ $formUrl }}"
                    hx-trigger="change"
                    hx-include="#event_type, [name='anchor']"
                    hx-select="#anchor-field"
                    hx-target="#anchor-field"
                    hx-swap="outerHTML">
                    <option value="">{{ __('notification_rule.select') }}</option>
                    @foreach ($eventTypes as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected ($selectedEventType === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <fieldset
                id="anchor-field"
                class="form-group"
                aria-describedby="anchor-hint anchor-error">
                <legend>
                    {{ __('notification_rule.anchor') }}
                    <span class="label__addition">({{ __('general.required') }})</span>
                </legend>
                <p
                    id="anchor-hint"
                    class="form-legend__help">
                    {{ __('notification_rule.anchor_hint') }}
                </p>
                <x-input-error
                    id="anchor-error"
                    :messages="$errors->get('anchor')" />
                <div class="radio-group radio-group--inline">
                    @foreach (NotificationRuleAnchor::cases() as $anchor)
                        @if ($anchor !== NotificationRuleAnchor::EVENT_END_DATE || $eventTypeHasEnd)
                            <div class="radio">
                                <input
                                    type="radio"
                                    id="anchor-{{ $anchor->value }}"
                                    name="anchor"
                                    value="{{ $anchor->value }}"
                                    @checked ($selectedAnchor === $anchor->value) />
                                <label for="anchor-{{ $anchor->value }}">
                                    {{ __('notification_rule.anchors.' . $anchor->value) }}
                                </label>
                            </div>
                        @endif
                    @endforeach
                </div>
            </fieldset>
            <fieldset
                class="form-group"
                aria-describedby="timing-hint timing-error">
                <legend>
                    {{ __('notification_rule.timing') }}
                    <span class="label__addition">({{ __('general.required') }})</span>
                </legend>
                <p
                    id="timing-hint"
                    class="form-legend__help">
                    {{ __('notification_rule.timing_hint') }}
                </p>
                <x-input-error
                    id="timing-error"
                    :messages="$errors->get('timing')" />
                <div class="radio-group radio-group--inline">
                    @foreach (NotificationRuleTiming::cases() as $timing)
                        <div class="radio">
                            <input
                                type="radio"
                                id="timing-{{ $timing->value }}"
                                name="timing"
                                value="{{ $timing->value }}"
                                @checked ($selectedTiming === $timing->value) />
                            <label for="timing-{{ $timing->value }}">
                                {{ __('notification_rule.timings.' . $timing->value) }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </fieldset>
            <div class="notification-rule-days">
                <x-input-label
                    for="days"
                    required
                    :content="__('notification_rule.days_label')" />
                <p id="days-hint">{{ __('notification_rule.days_hint') }}</p>
                <x-input-error
                    id="days-error"
                    :messages="$errors->get('days')" />
                <div class="input-with-unit">
                    <x-text-input
                        id="days"
                        :hasError="$errors->has('days')"
                        type="number"
                        min="0"
                        max="365"
                        name="days"
                        aria-describedby="days-hint days-error"
                        :value="old('days', $notificationRule?->numberOfDays())" />
                    <span>{{ __('notification_rule.days_unit') }}</span>
                </div>
            </div>
            <div class="checkbox">
                <input
                    type="hidden"
                    name="active"
                    value="0" />
                <input
                    id="active"
                    type="checkbox"
                    name="active"
                    value="1"
                    @checked (old('active', $notificationRule?->active ?? true ? '1' : '0') === '1') />
                <x-input-label
                    for="active"
                    :content="__('notification_rule.active')" />
            </div>
            <div class="button-container">
                <x-primary-button> {{ $submitLabel }} </x-primary-button>
                <button
                    type="button"
                    class="button secondary"
                    hx-post="{{ route(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_PREVIEW, ['department' => $department]) }}"
                    hx-target="#notification-rule-preview">
                    {{ __('notification_rule.show_preview') }}
                </button>
                <a
                    class="button"
                    href="{{ route(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_INDEX, ['department' => $department]) }}">
                    {{ __('general.cancel') }}
                </a>
            </div>
        </form>
    </div>
</section>
<section
    id="notification-rule-preview"
    class="mt-5"
    aria-live="polite"></section>
