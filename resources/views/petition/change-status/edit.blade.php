@section('pageTitle', __('petition.edit_status'))

<x-form-layout>
    <x-petition-header-details
        :petition="$petition"
        :hasBackLink="true"
        :backLinkRoute="route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $petition->department->slug, 'petition' => $petition])"
        :backLinkLabel="__('general.back_to_petition')" />

    <section class="mt-5">
        <div class="visually-grouped">
            <section>
                <form
                    method="post"
                    action="{{ route(RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_EDIT, ['department' => $petition->department->slug, 'petition' => $petition]) }}">
                    @csrf
                    <div class="form-input-group">
                        <label
                            class="form-label"
                            for="petition_status_id">
                            {{ __('petition.status') }}
                        </label>
                        <select
                            id="petition_status_id"
                            class="form-select"
                            name="petition_status_id">
                            @foreach ($petitionStatuses as $status)
                                <option
                                    value="{{ $status->id }}"
                                    @selected($status->id->toString() === Form::old('status_id', $petition->petition_status_id->toString()))>
                                    {{ $status->status }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label
                            for="petition_status_date"
                            required
                            :content="__('petition.status_date')" />
                        <x-input-error
                            id="petition_status_date-error"
                            :messages="$errors->get('petition_status_date')" />
                        <input
                            id="petition_status_date-date"
                            :hasError="$errors->has('petition_status_date')"
                            class="form-control"
                            type="date"
                            name="petition_status_date"
                            step="1"
                            value="{{ Form::old('petition_status_date', now()->format('Y-m-d')) }}" />
                    </div>

                    <div>
                        <x-input-label
                            for="petition_status_comment"
                            :content="__('petition.status_comment')" />
                        <x-input-error
                            id="petition_status_comment-error"
                            :messages="$errors->get('petition_status_comment')" />
                        <input
                            id="petition_status_comment-date"
                            :hasError="$errors->has('petition_status_comment')"
                            class="form-control"
                            type="text"
                            name="petition_status_comment"
                            value="{{ Form::old('petition_status_comment') }}" />
                    </div>

                    @if ($errors->any())
                        <x-notification type="danger">
                            <p>@lang('validation.global_message')</p>
                        </x-notification>
                    @endif

                    <div class="button-container">
                        <x-primary-button>{{ __('general.save') }}</x-primary-button>
                        <a
                            href="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $petition->department->slug, 'petition' => $petition]) }}"
                            class="button">
                            {{ __('general.cancel') }}
                        </a>
                    </div>
                </form>
            </section>
        </div>
    </section>
</x-form-layout>
