@props([
    'contacts',
    'title',
    'petition',
])

@if ($contacts->isNotEmpty())
    @if ($contacts->count() < 3)
        <dl class="description-list">
            <div class="description-list__item">
                <dt>{{ $title }}</dt>
                @foreach ($contacts as $contact)
                    <dd>
                        <a
                            href="{{ route(RouteName::DEPARTMENTS_CONTACTS_SHOW, ['department' => $petition->department, 'contact' => $contact]) }}">
                            <p>
                                {{ $contact->initials }}
                                {{ $contact->last_name }}
                            </p>
                            <p>{{ $contact->organisation_name }}</p>
                        </a>
                        @if ($contact->pivot->correspondence_preference || $contact->pivot->reference)
                            <p>
                                {{ __('contact.correspondence') . ':' }}
                                @if ($contact->pivot->correspondence_preference && $contact->pivot->reference)
                                    {{ __('contact.correspondence_preference_enum.' . $contact->pivot->correspondence_preference->value) }},
                                    {{ $contact->pivot->reference }}
                                @elseif ($contact->pivot->correspondence_preference)
                                    {{ __('contact.correspondence_preference_enum.' . $contact->pivot->correspondence_preference->value) }}
                                @elseif ($contact->pivot->reference)
                                    {{ $contact->pivot->reference }}
                                @endif
                            </p>
                        @endif
                    </dd>
                @endforeach
            </div>
        </dl>
    @else
        <dl class="description-list">
            <div class="description-list__item">
                <dt>{{ $title }}</dt>
                <dd>
                    <a
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CONTACTS_ATTACH_FORM, ['department' => $petition->department, 'petition' => $petition]) }}">
                        <p>{{ $contacts->count() }} {{ $title }}</p>
                    </a>
                </dd>
            </div>
        </dl>
    @endif
@endif
