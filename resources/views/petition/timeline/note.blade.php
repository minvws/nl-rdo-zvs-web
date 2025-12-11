<div class="timeline-item timeline-item--note">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            {{ Str::initials($timelineItem->user->name) }}
        </div>
        <div class="timeline-item__content">
            <x-timeline.timeline-header>
                <p>
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
            <div class="timeline-item__note">
                {{ $timelineItem->data->comment }}
            </div>

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
        </div>
    </div>
</div>
