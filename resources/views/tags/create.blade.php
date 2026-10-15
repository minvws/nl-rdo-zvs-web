@use(App\Enums\RouteName)
@use(App\Enums\TagColor)

@section('pageTitle', __('tag.create'))

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('tag.create') }}</h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <form
                method="post"
                action="{{ route(RouteName::DEPARTMENTS_ADMIN_TAGS_CREATE, ['department' => $department]) }}">
                @csrf
                <div>
                    <x-input-label
                        for="name"
                        required
                        :content="__('tag.name')" />
                    <x-input-error
                        id="name-error"
                        :messages="$errors->get('name')" />
                    <x-text-input
                        maxlength="64"
                        id="name"
                        :hasError="$errors->has('name')"
                        type="text"
                        name="name"
                        aria-describedby="name-error"
                        :value="old('name')" />
                </div>
                <div>
                    <x-input-label
                        for="color"
                        required
                        :content="__('tag.color')" />
                    <x-input-error
                        id="color-error"
                        :messages="$errors->get('color')" />
                    <select
                        class="form-select @error('color') input-error @enderror"
                        id="color"
                        name="color"
                        aria-describedby="color-error">
                        @foreach (TagColor::cases() as $tagColor)
                            <option
                                value="{{ $tagColor->value }}"
                                @selected(old('color') === $tagColor->value)>
                                {{ __('tag_color.' . $tagColor->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label
                        for="description"
                        :content="__('tag.description')" />
                    <x-input-error
                        id="description-error"
                        :messages="$errors->get('description')" />
                    <x-text-input
                        maxlength="255"
                        id="description"
                        :hasError="$errors->has('description')"
                        type="text"
                        name="description"
                        aria-describedby="description-error description-hint"
                        :value="old('description')" />
                    <p id="description-hint">{{ __('tag.description_hint') }}</p>
                </div>
                <div class="checkbox">
                    <input
                        type="hidden"
                        name="active"
                        value="0" />
                    <input
                        id="active"
                        type="checkbox"
                        name="active"
                        value="1"
                        @checked(old('active', '1') === '1') />
                    <x-input-label
                        for="active"
                        :content="__('tag.active')" />
                </div>
                <div class="button-container">
                    <x-primary-button>
                        {{ __('tag.create') }}
                    </x-primary-button>
                    <a
                        class="button"
                        href="{{ route(RouteName::DEPARTMENTS_ADMIN_TAGS_INDEX, ['department' => $department]) }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-form-layout>
