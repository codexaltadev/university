{{-- Program page tab "Accreditation" (FR-ACR-05). Contract: $entity, $items (in order) --}}
<div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(320px,1fr))">
    @foreach ($items as $accreditation)
        @include('accreditation::partials.accreditation', ['accreditation' => $accreditation, 'base' => ''])
    @endforeach
</div>
