@props([
    'footer' => null,
    'processingSteps' => [],
    'decision',
    'title',
])
<section class="mt-5">
    <h2>{{ $title }}</h2>
    <table>
        <thead>
            <tr>
                <th scope="col">{{ __('processing-step.name') }}</th>
                <th scope="col">{{ __('processing-step.assigned-user') }}</th>
                <th scope="col">{{ __('processing-step.deadline') }}</th>
                <th scope="col">{{ __('processing-step.status') }}</th>
                <th
                    class="action-column"
                    scope="col">
                    &nbsp;
                </th>
                <th
                    class="action-column"
                    scope="col">
                    &nbsp;
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($processingSteps as $processingStep)
                <tr>
                    <th scope="row">{{ $processingStep->name }}</th>
                    <td>{{ $processingStep->assignedUser?->name }}</td>
                    <td>{{ $processingStep->deadline_at ? DisplayDate::date($processingStep->deadline_at) : '-' }}</td>
                    <td>
                        <span class="tag tag--{{ $processingStep->status->value }}">
                            {{ __('processing-step.' . $processingStep->status->value) }}
                        </span>
                    </td>
                    <td>
                        @if ($decision->archived_at === null)
                            <a
                                class="icon-only"
                                href="
                                {{
                                    route(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_EDIT, [
                                        'department' => $decision->department,
                                        'decision' => $decision,
                                        'processingStep' => $processingStep,
                                    ])
                                }}
                                ">
                                <span class="visually-hidden"></span>
                                <x-tabler-chevron-right />
                            </a>
                        @endif
                    </td>
                    <td>
                        @if ($decision->archived_at === null)
                            <a
                                class="icon-only"
                                href="
                                {{
                                    route(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_DELETE, [
                                        'department' => $decision->department,
                                        'decision' => $decision,
                                        'processingStep' => $processingStep,
                                    ])
                                }}
                                ">
                                <span class="visually-hidden"></span>
                                <x-tabler-trash />
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">
                        {{ __('processing-step.no_processing_steps') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">
                    {{ $footer }}
                </td>
            </tr>
        </tfoot>
    </table>
</section>
