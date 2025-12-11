<div class="timeline-item timeline-item--occurrence">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            <x-tabler-clock
                aria-hidden="true"
                focusable="false" />
        </div>
        <div class="timeline-item__content timeline-item__content--fallback">
            <x-timeline.timeline-header>
                {{ __('timeline.fallback_message', ['type' => $type->value, 'id' => $id, 'contact' => config('app.support_contact', 'support')]) }}
            </x-timeline.timeline-header>
        </div>
    </div>
</div>
