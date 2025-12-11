@use(Illuminate\Support\Facades\Request)

@props([
    "route",
    "icon",
])

<li>
    <a
        @if(Request::getUri()===$route) aria-current="page" @endif
        @foreach ($attributes as $name => $values)
            {{ $name }}="{{ $values }}"
        @endforeach
        href="{{ $route }}">
        {{ $slot }}
    </a>
</li>
