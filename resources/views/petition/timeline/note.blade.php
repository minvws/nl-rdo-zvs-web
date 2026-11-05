@use (App\Enums\Ability)
@use (Illuminate\Support\Facades\Crypt)

<div class="timeline-item timeline-item--note">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">{{ Str::customInitials($timelineItem->user->name) }}</div>
        <div class="timeline-item__content">
            <x-timeline.timeline-header>
                <p class="text-wrap-normal">
                    <span>{{ $timelineItem->user->name }}</span>
                    {{ __('timeline.note_added') }} {{ DisplayDate::sentence($timelineItem->created_at) }}
                </p>
            </x-timeline.timeline-header>
            <p class="fw-bold">
                {{ __('petition.note') }}
                <x-tabler-message-dots
                    aria-hidden="true"
                    focusable="false" />
            </p>
            <div class="timeline-item__note">{{ $timelineItem->data->comment }}</div>

            @if ($attachments->isNotEmpty())
                <div class="timeline-item__attachments mt-4">
                    <p class="fw-bold">
                        {{ __('attachment.files') }}
                        <x-tabler-paperclip
                            aria-hidden="true"
                            focusable="false" />
                    </p>
                    <ul class="timeline-item__attachments-list mt-3">
                        @foreach ($attachments as $attachment)
                            <li class="timeline-item__attachment">
                                <a
                                    class="timeline-item__attachment-link"
                                    href="{{ route('attachments.download', ['attachment' => $attachment]) }}"
                                    target="_blank">
                                    {{ $attachment->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @can (Ability::UPDATE, $timelineable)
                <a
                    class="icon-only icon-only--xsmall icon-only--delete timeline-item__delete"
                    title="{{ __('general.delete') }}"
                    href="{{
                        route(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_CONFIRM_DELETE, [
                            'department' => $timelineable->department,
                            'timelineableType' => $timelineable->getMorphClass(),
                            'timelineable' => $timelineable->getKey(),
                            'url' => Crypt::encryptString(url()->full()),
                            'timelineItem' => $timelineItem->internal_id,
                        ])
                    }}">
                    <span class="visually-hidden">{{ __('general.delete') }}</span>
                    <x-tabler-trash
                        aria-hidden="true"
                        focusable="false" />
                </a>
            @endcan
        </div>
    </div>
</div>
