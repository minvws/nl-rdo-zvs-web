@forelse ($particularities as $particularity)
    <span class="tag tag--danger">{{ $particularity }}</span>
@empty
    -
@endforelse
