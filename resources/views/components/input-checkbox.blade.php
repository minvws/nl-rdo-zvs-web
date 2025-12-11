<div class="checkbox">
    <input
        type="checkbox"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
        @checked($checked) />
    <x-input-label
        for="{{ $id }}"
        :content="$name" />
</div>
