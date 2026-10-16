{{-- Event page "Photo albums" (FR-GAL-06). Contract: $entity (the event), $albums --}}
<div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(220px,1fr))">
    @foreach ($albums as $album)
        @include('gallery::partials.card', ['album' => $album, 'base' => '', 'layout' => 'cards'])
    @endforeach
</div>
