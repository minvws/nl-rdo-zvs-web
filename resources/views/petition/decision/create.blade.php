@use(App\Enums\DecisionType)
@use(App\Enums\RouteName)

@section('pageTitle', __('decision.create'))

<x-form-layout>
    <h1>{{ __('decision.create') }}</h1>
    <form
        method="POST"
        action="{{
            $petition
                ? route(RouteName::DEPARTMENTS_PETITIONS_DECISIONS_STORE, ['department' => $petition->department->slug, 'petition' => $petition])
                : route(RouteName::DEPARTMENTS_DECISIONS_STORE, ['department' => $department])
        }}">
        @csrf
        <div class="form-group">
            <x-input-label
                for="name"
                required
                :content="__('decision.name')" />
            <x-input-error
                id="name-error"
                :messages="$errors->get('name')" />
            <x-text-input
                id="name"
                class="form-control"
                :hasError="$errors->has('name')"
                name="name"
                aria-describedby="name-error" />
        </div>
        <div class="form-group">
            <x-input-label
                for="reference"
                :content="__('decision.reference')" />
            <x-input-error
                id="reference-error"
                :messages="$errors->get('reference')" />
            <x-text-input
                id="reference"
                class="form-control"
                :hasError="$errors->has('reference')"
                name="reference"
                aria-describedby="reference-error" />
        </div>
        <div class="form-group">
            <x-input-label
                for="date"
                :content="__('decision.date')" />
            <input
                id="date"
                type="date"
                class="form-control"
                name="date" />
        </div>
        <div class="form-group">
            <x-input-label
                for="type"
                required
                :content="__('decision.type.label')" />
            <x-input-error
                id="type-error"
                :messages="$errors->get('type')" />
            <select
                id="type"
                name="type"
                class="form-select"
                aria-describedby="type-error">
                <option value="">{{ __('general.select') }}</option>
                @foreach (DecisionType::cases() as $decisionType)
                    <option
                        value="{{ $decisionType->value }}"
                        @selected(old('type') === $decisionType->value)>
                        {{ $decisionType->label() }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="button-container">
            <x-primary-button>
                {{ __('general.create') }}
            </x-primary-button>
            <a
                class="button"
                href="{{
                    $petition
                        ? route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $petition->department->slug, 'petition' => $petition])
                        : route(RouteName::DEPARTMENTS_DECISIONS_INDEX, ['department' => $department])
                }}">
                {{ __('general.cancel') }}
            </a>
        </div>
    </form>
</x-form-layout>
