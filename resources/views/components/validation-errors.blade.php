@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'error']) }}>
        <p>
            <span>{{ __('Error:') }}</span>
            {{ __('Whoops! Something went wrong.') }}
        </p>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
