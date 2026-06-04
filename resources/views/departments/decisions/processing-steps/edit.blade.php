@section('pageTitle', __('processing-step.edit'))

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('processing-step.edit') }}</h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <form
                action="{{
                    route(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_UPDATE, [
                        'department' => $department,
                        'decision' => $decision,
                        'processingStep' => $processingStep,
                    ])
                }}"
                method="POST">
                @csrf

                <div class="form-group">
                    <x-input-label
                        for="name"
                        :content="__('processing-step.name')" />

                    <x-input-error
                        id="name-error"
                        :messages="$errors->get('name')" />

                    <x-text-input
                        id="name"
                        class="form-control"
                        list="processing-steps"
                        :hasError="$errors->has('name')"
                        name="name"
                        aria-describedby="name-error"
                        value="{{ old('name', $processingStep->name) }}" />

                    @if ($options->isNotEmpty())
                        <datalist id="processing-steps">
                            @foreach ($options as $option)
                                <option>{{ $option }}</option>
                            @endforeach
                        </datalist>
                    @endif
                </div>

                <div class="form-group">
                    <x-input-label
                        for="deadline_at"
                        :content="__('processing-step.deadline')" />
                    <x-input-error
                        id="deadline_at-error"
                        :messages="$errors->get('deadline_at')" />
                    <input
                        type="date"
                        class="form-control @error('deadline_at') input-error @enderror"
                        id="deadline_at"
                        name="deadline_at"
                        aria-describedby="deadline_at-error"
                        value="{{ old('deadline_at', $processingStep->deadline_at?->format('Y-m-d')) }}" />
                </div>

                <div class="form-group">
                    <x-input-label
                        for="status"
                        :content="__('processing-step.status')" />
                    <x-input-error
                        id="status-error"
                        :messages="$errors->get('status')" />
                    <select
                        class="form-select @error('status') input-error @enderror"
                        id="status"
                        name="status"
                        aria-describedby="status-error">
                        @foreach ($statuses as $status)
                            <option
                                value="{{ $status->value }}"
                                @selected($status->value === old('status', $processingStep->status->value))>
                                {{ __('processing-step.' . $status->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <x-input-label
                        for="first_assignee"
                        :content="__('processing-step.assigned_to')" />
                    <x-input-error
                        id="first_assignee-error"
                        :messages="$errors->get('first_assignee')" />
                    <select
                        class="form-select @error('first_assignee') input-error @enderror"
                        id="first_assignee"
                        name="first_assignee"
                        aria-describedby="first_assignee-error">
                        <option
                            value=""
                            @selected('' === old('first_assignee'))>
                            {{ __('general.none') }}
                        </option>
                        @foreach ($users as $id => $name)
                            <option
                                value="{{ $id }}"
                                @selected($id === old('first_assignee', $processingStep->firstAssignee?->user_id?->toString()))>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <x-input-label
                        for="second_assignee"
                        :content="__('processing-step.secondary_assigned_to')" />
                    <x-input-error
                        id="second_assignee-error"
                        :messages="$errors->get('second_assignee')" />
                    <select
                        class="form-select @error('second_assignee') input-error @enderror"
                        id="second_assignee"
                        name="second_assignee"
                        aria-describedby="second_assignee-error">
                        <option
                            value=""
                            @selected('' === old('second_assignee'))>
                            {{ __('general.none') }}
                        </option>
                        @foreach ($users as $id => $name)
                            <option
                                value="{{ $id }}"
                                @selected($id === old('second_assignee', $processingStep->secondAssignee?->user_id?->toString()))>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="button-container">
                    <x-primary-button>
                        {{ __('general.save') }}
                    </x-primary-button>
                    <a
                        class="button"
                        href="{{
                            route(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                                'department' => $department,
                                'decision' => $decision,
                            ])
                        }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-form-layout>
