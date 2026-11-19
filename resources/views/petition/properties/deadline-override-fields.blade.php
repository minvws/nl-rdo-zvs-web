<div>
    <x-input-label
        for="petition-deadline-override-at"
        :content="__('petition.deadline_override_at')" />
    <x-input-error
        id="deadline_override_at-error"
        :messages="$errors->get('deadline_override_at')" />
    <input
        id="petition-deadline-override-at"
        class="form-control"
        type="date"
        name="deadline_override_at"
        aria-describedby="deadline_override_at-error"
        step="1"
        value="{{ Form::old("deadline_override_at", $petition->deadline_override_at) }}" />
</div>

<div>
    <x-input-label
        for="petition-deadline-override-reason"
        :content="__('petition.deadline_override_reason')" />
    <x-input-error
        id="deadline_override_reason-error"
        :messages="$errors->get('deadline_override_reason')" />
    <x-text-input
        id="petition-deadline-override-reason"
        :hasError="$errors->has('deadline_override_reason')"
        name="deadline_override_reason"
        type="text"
        aria-describedby="deadline_override_reason-error"
        value="{{ Form::old('deadline_override_reason', $petition->deadline_override_reason) }}" />
</div>
