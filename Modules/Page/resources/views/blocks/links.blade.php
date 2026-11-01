{{-- Link cards block. Contract: $data['heading'], $data['items'] (label, url — http(s) or /path, checked on save, may be empty — text, icon, new_tab) --}}
<section class="block block--links">
    @if (filled($data['heading'] ?? null))<h2>{{ $data['heading'] }}</h2>@endif
    <ul style="list-style:none;padding:0;display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(220px,1fr))">
        @foreach ((array) ($data['items'] ?? []) as $item)
            <li class="card" style="padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius-md, 8px)">
                @if (filled($item['url'] ?? null))<a href="{{ $item['url'] }}" @if ($item['new_tab'] ?? false) target="_blank" rel="noopener" @endif>{{ $item['label'] ?? '' }}</a>@else<strong>{{ $item['label'] ?? '' }}</strong>@endif
                @if (filled($item['text'] ?? null))<p style="margin:.25rem 0 0">{{ $item['text'] }}</p>@endif
            </li>
        @endforeach
    </ul>
</section>
