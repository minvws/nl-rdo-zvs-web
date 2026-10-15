@props([
    'tags',
    'limit' => null,
    'empty' => null,
])

@php
    $visibleTags = $limit === null ? $tags : $tags->take($limit);
    $remainingNames = $tags
        ->slice($visibleTags->count())
        ->pluck('name')
        ->implode(', ');
    $remainingCount = $tags->count() - $visibleTags->count();
@endphp

@if ($tags->isNotEmpty())
    <div class="tags horizontal-view">
        @foreach ($visibleTags as $tag)
            <x-tag.chip :tag="$tag" />
        @endforeach

        @if ($remainingCount > 0)
            <span
                class="tag tag--color-gray"
                title="{{ $remainingNames }}">
                <span aria-hidden="true">+{{ $remainingCount }}</span>
                <span class="visually-hidden">
                    {{ trans_choice('tag.more', $remainingCount, ['names' => $remainingNames]) }}
                </span>
            </span>
        @endif
    </div>
@else
    {{ $empty ?? __('tag.none') }}
@endif
