{{-- Homepage section "Important Links" (FR-LINK-02). Contract: $items (Link list), $section, $settings (group, display, count); $context from the page --}}
@if ($items->isNotEmpty())
    @php($tiles = ($settings['display'] ?? 'tiles') === 'tiles')
    <div style="{{ $tiles ? 'display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(160px,1fr))' : '' }}">
        @foreach ($items as $link)
            @include('links::partials.link', ['link' => $link, 'layout' => $tiles ? 'tiles' : 'list'])
        @endforeach
    </div>
@endif
