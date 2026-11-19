@use (App\Facades\DisplayDate)

@props ([
    'noteAttachments',
    'timelineableUrl',
])
<section
    id="note-attachments"
    class="mt-5"
    hx-trigger="eventPetitionUpdated-timeline from:body"
    hx-get="{{ $timelineableUrl }}"
    hx-select="#note-attachments"
    hx-swap="outerHTML"
    aria-live="polite">
    <h2 class="mb-3">{{ __('attachment.files') }}</h2>
    <table>
        <thead>
            <tr>
                <th scope="col">{{ __('general.name') }}</th>
                <th scope="col">{{ __('attachment.note_date') }}</th>
                <th
                    class="action-column"
                    scope="col">
                    &nbsp;
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($noteAttachments as $noteAttachment)
                <tr>
                    <th scope="row">
                        <a
                            href="{{ route('attachments.download', ['attachment' => $noteAttachment['attachment']]) }}"
                            target="_blank">
                            {{ $noteAttachment['attachment']->name }}
                        </a>
                    </th>
                    <td>{{ DisplayDate::date($noteAttachment['note']->created_at) }}</td>
                    <td>
                        <a
                            class="icon-only"
                            href="{{ $timelineableUrl }}#timeline-item-{{ $noteAttachment['note']->internal_id }}"
                            hx-get="{{ $timelineableUrl }}"
                            hx-push-url="{{ $timelineableUrl }}#timeline-item-{{ $noteAttachment['note']->internal_id }}"
                            hx-target="#timeline-container"
                            hx-select="#timeline-container"
                            hx-select-oob="#timeline_filter_group:innerHTML"
                            hx-swap="outerHTML show:#timeline-item-{{ $noteAttachment['note']->internal_id }}:top">
                            <span class="visually-hidden">{{ __('attachment.go_to_note') }}</span>
                            <x-tabler-chevron-right
                                aria-hidden="true"
                                focusable="false" />
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">{{ __('attachment.no_records') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
