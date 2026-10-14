{{-- Blurred city silhouette with birds rising from the rooftops (pure CSS, endless loop). --}}
<div class="backdrop" aria-hidden="true"></div>
<div class="birds" aria-hidden="true">
    @foreach (range(1, 7) as $bird)
        <span class="bird bird-{{ $bird }}">
            <svg viewBox="0 0 40 14"><path d="M0 9 Q 10 0 20 9 Q 30 0 40 9 Q 30 4 20 12 Q 10 4 0 9 Z"/></svg>
        </span>
    @endforeach
</div>
