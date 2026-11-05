@use (App\Enums\RouteName)

<h2 class="mb-3">{{ __('term.added_objection_events') }}</h2>
<x-input-error
    id="delete-error"
    :messages="$errors->get('delete')" />
<table class="objection-events-events-table">
    <thead>
        <tr>
            <th>Type</th>
            <th>{{ __('term.date') }}</th>
            <th>{{ __('term.term') }}</th>
            <th>{{ __('term.penalty') }}</th>
            <th>{{ __('term.reasoning') }}</th>
            <th>{{ __('term.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($events->all() as $event)
            <tr>
                <td>
                    <strong>{{ $event->type->label($petition->petitionType->type) }}</strong>
                </td>
                <td>{{ $event->date->toDateString() }}</td>
                <td>{{ $termText($event) }}</td>
                <td>
                    @if (! empty($event->penalties))
                        <div>
                            @foreach ($event->penalties as $penalty)
                                <span>
                                    {{ $penalty->duration > 0 ? $penalty->duration . ' dagen' : '?' }}
                                    @if ($penalty->amount > 0)
                                        : €{{ number_format($penalty->amount, 0, ',', '.') }}
                                    @endif
                                </span>
                                @if (! $loop->last)
                                    /
                                @endif
                            @endforeach
                        </div>
                    @endif
                </td>
                <td>
                    @if (! empty($event->reasoning))
                        @if ($event->type->reasoningSelectEnumClass() !== null)
                            @php
                                $enumClass = $event->type->reasoningSelectEnumClass();
                                $enum = $enumClass::from($event->reasoning);
                            @endphp

                            {{ $enum->label() }}
                        @else
                            {{ $event->reasoning }}
                        @endif
                    @endif
                </td>
                <td>
                    @if ($canDelete($event))
                        <form
                            method="POST"
                            action="{{ route(RouteName::PETITION_EVENTS_WIZARD_DELETE, ['department' => $petition->department, 'petition' => $petition, 'event' => $event->id->toString()]) }}"
                            class="flex flex-center">
                            @csrf
                            <button
                                class="icon-only--xsmall icon-only--delete"
                                type="submit"
                                title="{{ __('term.delete_event') }}">
                                <x-tabler-trash />
                            </button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5">
                    <div class="mt-2">{{ __('term.no_objection_events_calendar_items') }}</div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
