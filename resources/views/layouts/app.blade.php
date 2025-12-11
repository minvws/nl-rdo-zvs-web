@use(Illuminate\Support\Facades\Config)
@use(App\Facades\ActiveDepartment)

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
        @vite(['resources/css/app.scss', 'resources/js/app.js', 'resources/js/auto-submit.js', 'resources/js/hide-table-columns.js', 'resources/js/scrollable-table.js', 'resources/css/manon.scss'])
    </head>

    <body
        data-active-department="{{ ActiveDepartment::getActiveDepartment()?->slug }}"
        data-active-department-hide-column-defaults="{{ ActiveDepartment::getActiveDepartment()?->hide_column_defaults }}">
        <x-header>
            <x-navigation />
        </x-header>
        <!-- Page Content -->
        <main
            class="{{ $withSidemenu ?? false ? 'sidemenu' : '' }} mt-5"
            id="main-content"
            tabindex="-1">
            <!-- Flash message -->
            @if (is_string(Session::get('message.success')))
                <x-notification
                    type="success"
                    dismissible>
                    {{ Session::get('message.success') }}
                </x-notification>
            @endif

            <div class="container">
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
