@use(App\Enums\RouteName)
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

    <section class="mt-5">
        <div class="visually-grouped">
            <h2 class="mb-3">{{ __('petition.status_history') }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('petition.status') }}</th>
                        <th>{{ __('general.date') }}</th>
                        <th>{{ __('petition.status_comment') }}</th>
                        <th>{{ __('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($statusHistory as $history)
                        <tr @if($loop->last) style="background-color: #e8e8e8" @endif>
                            <td>
                                <strong>
                                    {{ $history->petitionStatus->status }}
                                </strong>
                            </td>
                            <td>{{ $history->date->toDateString() }}</td>
                            <td>{{ $history->comment }}</td>
                            <td>
                                @if ($statusHistory->count() > 1)
                                    <a
                                        href="{{
                                            confirmRoute(
                                                route(RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_DESTROY, [
                                                    'department' => $petition->department->slug,
                                                    'petition' => $petition,
                                                    'petitionStatusHistory' => $history,
                                                ]),
                                                route(RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_EDIT, [
                                                    'department' => $petition->department->slug,
                                                    'petition' => $petition,
                                                ]),
                                                __('general.delete') . ' ' . $history->petitionStatus->status . ' - ' . $history->date->toDateString() . '?',
                                                'DELETE',
                                            )
                                        }}"
                                        class="icon-only--xsmall icon-only--delete"
                                        title="{{ __('general.delete') }}">
                                        <x-tabler-trash />
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="mt-2">{{ __('petition.no_status_history') }}</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-form-layout>
