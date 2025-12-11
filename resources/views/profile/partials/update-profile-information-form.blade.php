<section class="visually-grouped">
    <div class="spacing-0">
        <h2>
            {{ __('profile.personal.title') }}
        </h2>

        <p>
            {{ __('profile.personal.subtitle') }}
        </p>
    </div>

    <form
        id="send-verification"
        method="post"
        action="{{ route('verification.send') }}"
        class="visually-hidden">
        @csrf
    </form>

    <form
        method="post"
        action="{{ route('profile.edit') }}">
        @csrf

        <div>
            <x-input-label
                for="name"
                required
                :content="__('user.name')" />
            <x-input-error
                id="name-error"
                :messages="$errors->get('name')" />
            <x-text-input
                id="name"
                :hasError="$errors->has('name')"
                name="name"
                type="text"
                aria-describedby="name-error"
                :value="old('name', $user->name)"
                autofocus
                autocomplete="name" />
        </div>

        <div>
            <x-primary-button>{{ __('general.save') }}</x-primary-button>
        </div>
    </form>
</section>
