@props([
    'block_title',
    'block_route' => null,
])

<div class="petition-property__block">
    <header class="petition-property__header">
        <h2 class="petition-property__title">{{ $block_title }}</h2>

        @if ($block_route)
            <a
                class="icon-only petition-property__edit"
                href="{{ $block_route }}">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">
                    {{ __('general.edit') }}
                </span>
            </a>
        @endif
    </header>
    <div class="petition-property__content">
        {{ $slot }}
    </div>
</div>
