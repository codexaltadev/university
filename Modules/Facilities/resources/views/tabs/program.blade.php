{{-- Program page tab "Facilities" (FR-FCL-05). Contract: $entity, $items (in order) --}}
<div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
    @foreach ($items as $facility)
        @include('facilities::partials.card', ['facility' => $facility, 'base' => '', 'layout' => 'cards'])
    @endforeach
</div>
