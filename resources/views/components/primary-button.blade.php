<button {{ $attributes->merge(['type' => 'submit', 'class' => 'cta']) }}>
    {{ $slot }}
</button>
