@use(App\Enums\RouteName)
@use(App\Models\Decision)
@use(App\Facades\DisplayDate)

@section('pageTitle', __('decision.set_final_decision'))

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('decision.set_final_decision') }}</h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <form
                method="post"
                action="{{ route(RouteName::DEPARTMENTS_PETITIONS_FINAL_DECISION_UPDATE, ['department' => $department, 'petition' => $petition]) }}">
                @csrf

                <table>
                    <thead>
                        <tr>
                            <th scope="col"><span class="visually-hidden">{{ __('general.select') }}</span></th>
                            <th scope="col">{{ __('decision.name') }}</th>
                            <th scope="col">{{ __('decision.reference') }}</th>
                            <th scope="col">{{ __('decision.date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($petition->decisions as $decision)
                            <tr>
                                <td>
                                    <input
                                        type="radio"
                                        name="final_decision_id"
                                        id="decision-{{ $decision->id }}"
                                        value="{{ $decision->id }}"
                                        @checked($decision->pivot->is_final) />
                                </td>
                                <td>
                                    <label for="decision-{{ $decision->id }}">{{ $decision->name }}</label>
                                </td>
                                <td>{{ $decision->reference }}</td>
                                <td>{{ $decision->date ? DisplayDate::date($decision->date) : '-' }}</td>
                            </tr>
                        @endforeach

                        <tr>
                            <td>
                                <input
                                    type="radio"
                                    name="final_decision_id"
                                    id="final-decision-none"
                                    value=""
                                    @checked(! $petition->decisions->contains(fn (Decision $d): bool => $d->pivot->is_final)) />
                            </td>
                            <td
                                colspan="3"
                                style="text-align: left">
                                <label
                                    for="final-decision-none"
                                    style="white-space: normal">
                                    {{ __('decision.no_final_decision') }}
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <x-input-error
                    id="final-decision-id-error"
                    :messages="$errors->get('final_decision_id')" />

                <div class="button-container">
                    <x-primary-button>
                        {{ __('general.save') }}
                    </x-primary-button>
                    <a
                        class="button"
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $department, 'petition' => $petition]) }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-form-layout>
