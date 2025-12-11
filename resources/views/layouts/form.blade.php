@use(Illuminate\Support\Facades\Config)

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1" />
        <meta
            name="csrf-token"
            content="{{ csrf_token() }}" />

        <title>@yield('pageTitle') | {{ Config::string('app.name') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.scss', 'resources/js/app.js', 'resources/css/manon.scss'])
    </head>

    <body>
        <x-header>
            @if (auth()->check())
                <x-navigation />
            @endif
        </x-header>

        <!-- Page Content -->
        <main
            id="main-content"
            tabindex="-1"
            class="mt-5">
            <!-- Flash message -->
            @if (is_string(Session::get('message.success')))
                <x-notification
                    type="success"
                    dismissible>
                    {{ Session::get('message.success') }}
                </x-notification>
            @endif

            <div class="container container--narrow">
                <!-- Page Heading -->
                @isset($header)
                    <header>
                        {{ $header }}
                    </header>
                @endisset

                {{ $slot }}
            </div>
        </main>

        <x-footer />
    </body>
</html>
