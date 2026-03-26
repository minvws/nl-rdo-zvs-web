@use(Illuminate\Support\Facades\Crypt)

<div class="timeline-item">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            {{ Str::customInitials(Authentication::user()->name) }}
        </div>
        <div class="timeline-item__content">
            <div class="timeline-item__note">
                <form
                    method="POST"
                    class="timeline-item__form"
                    @ifHtmx
                        hx-target="#notes-block"
                        hx-post="{{
                            route(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_STORE, [
                                "department" => $department,
                                "timelineableType" => $timelineableType,
                                "timelineable" => $timelineableId,
                                "url" => $url,
                            ])
                        }}"
                    @endifHtmx
                    action="{{
                        route(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_STORE, [
                            "department" => $department,
                            "timelineableType" => $timelineableType,
                            "timelineable" => $timelineableId,
                            "url" => $url,
                        ])
                    }}"
                    enctype="multipart/form-data">
                    @csrf
                    <input
                        type="hidden"
                        name="hx-target"
                        value="notes-block" />
                    <legend>
                        {{ __("petition.new_note") }}
                        <x-tabler-message-dots
                            aria-hidden="true"
                            focusable="false" />
                    </legend>
                    @if ($errors->any())
                        <x-notification type="danger">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </x-notification>
                    @endif

                    <x-input-label
                        class="visually-hidden"
                        for="note"
                        :content="__('petition.note')" />
                    <textarea
                        class="timeline-item__note"
                        name="comment"
                        id="note"
                        rows="5"
                        placeholder="{{ __("petition.new_note_placeholder") }}"></textarea>
                    <x-input-label
                        class="visually-hidden"
                        for="attachments"
                        :content="__('petition.attachments')" />
                    <input
                        id="attachments"
                        type="file"
                        name="attachments[]"
                        multiple />
                    <div class="button-container">
                        <button
                            type="submit"
                            class="cta">
                            {{ __("general.save") }}
                        </button>
                        <a
                            href="{{ Crypt::decryptString($url) }}"
                            class="button"
                            hx-get=""
                            hx-select="#notes-block"
                            hx-target="#notes-block"
                            hx-swap="innerHTML">
                            {{ __("general.cancel") }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
