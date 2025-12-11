<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-outline-secondary btn-sm']) }}>
    {{ $slot }}
</button>
