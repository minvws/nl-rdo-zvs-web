@section("pageTitle", __("petition.edit_assigned_user"))

<div class="petition-property__block petition-property__block--active">
    @ifHtmx
        <header class="petition-property__header">
            <h2 class="petition-property__title">{{ __("petition.assigned_user") }}</h2>

            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_SHOW, ["department" => $petition->department, "petition" => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#assign-primary-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __("petition.assigned_user") }} {{ __("general.edit") }}</span>
            </a>
        </header>
    @endifHtmx

    <div class="petition-property__content">
        <form
            method="post"
            action="{{ route(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_UPDATE, ["department" => $petition->department, "petition" => $petition]) }}"
            hx-push-url="false"
            hx-swap="innerHTML"
            hx-target="#assign-primary-block">
            @csrf
            <input
                type="hidden"
                name="hx-target"
                value="assign-primary-block" />
            <h3 class="form-section__title form-section__title--border">{{ __("petition.edit_assigned_user") }}</h3>
            <div class="form-input-group">
                <x-input-label
                    class="visually-hidden"
                    for="user-id"
                    :content="__('petition.assigned_user')" />
                <x-input-error
                    id="user-error"
                    :messages="$errors->get('user_id')" />
                <select
                    aria-describedby="user-error"
                    id="user-id"
                    class="form-select @error("user_id") input-error @enderror"
                    name="user_id"
                    @error("user_id")
                        aria-invalid="true"
                    @enderror>
                    <option value="">(geen)</option>
                    @foreach ($users as $user)
                        <option
                            value="{{ $user->id }}"
                            @selected($user->id->toString() === Form::old("user_id", $petition->firstAssignee?->user_id?->toString()))>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if ($errors->any())
                <x-notification type="danger">
                    <p>@lang("validation.global_message")</p>
                </x-notification>
            @endif

            <div class="button-container">
                <x-primary-button>{{ __("petition.assign") }}</x-primary-button>
                <a
                    class="button"
                    @ifHtmx
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_SHOW, ["department" => $petition->department, "petition" => $petition]) }}"
                        hx-target="#assign-primary-block"
                        hx-swap="innerHTML"
                    @else
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ["department" => $petition->department, "petition" => $petition]) }}"
                    @endifHtmx>
                    {{ __("general.cancel") }}
                </a>
            </div>
        </form>
    </div>
</div>
