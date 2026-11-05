@use (Illuminate\Support\Facades\Crypt)

@section ('pageTitle', __('timeline.delete_note.title'))

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('timeline.delete_note.title') }}</h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <p class="text-wrap-normal">{{ __('timeline.delete_note.description') }}</p>

            <div class="timeline-item__note text-wrap-normal mt-3 mb-3">{{ $timelineItem->data->comment }}</div>

            <form
                method="POST"
                action="{{
                    route(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_DELETE, [
                        'department' => $department,
                        'timelineableType' => $timelineableType,
                        'timelineable' => $timelineableId,
                        'url' => $url,
                        'timelineItem' => $timelineItem->internal_id,
                    ])
                }}">
                @csrf

                <div class="form-group">
                    <x-input-label
                        for="reason"
                        :content="__('timeline.delete_note.reason')" />
                    <x-input-error
                        id="reason-error"
                        :messages="$errors->get('reason')" />
                    <textarea
                        class="form-control @error('reason') input-error @enderror"
                        id="reason"
                        name="reason"
                        rows="4"
                        placeholder="{{ __('timeline.delete_note.reason_hint') }}"
                        aria-describedby="reason-error"
                        >{{ old('reason') }}</textarea
                    >
                </div>

                <div class="button-container">
                    <button
                        type="submit"
                        class="cta">
                        {{ __('general.delete') }}
                    </button>
                    <a
                        class="button"
                        href="{{ Crypt::decryptString($url) }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-form-layout>
