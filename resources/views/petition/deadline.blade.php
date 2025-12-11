<div
    class="form-input-group"
    id="deadline-container">
    <x-input-label
        for="deadline-at"
        :content="__('petition.deadline_at')" />
    <x-input-error
        id="deadline-error"
        :messages="$errors->get('deadline_at')" />
    <input
        class="form-control @error("deadline_at") input-error @enderror"
        id="deadline-at"
        aria-describedby="deadline-error"
        @error("deadline_at")
            aria-invalid="true"
        @enderror
        type="date"
        name="deadline_at"
        value="{{ Form::old('deadline_at', $deadline_at) }}"
        step="1" />
</div>
