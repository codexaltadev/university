{{-- Footer block "Important Links" (FR-LINK-02). Contract: $block (links_group, links_count), $context, $links (Link list) --}}
@if ($links->isNotEmpty())
    <ul class="site-footer__links" style="list-style:none;padding:0;margin:0;display:grid;gap:var(--space-2, 8px)">
        @foreach ($links as $link)
            <li>@include('links::partials.link', ['link' => $link, 'layout' => 'footer'])</li>
        @endforeach
    </ul>
@endif
