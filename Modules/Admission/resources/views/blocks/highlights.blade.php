{{-- Admission information cards block (FR-ADM-15). Contract: $data (categories, grouped), $locale --}}
@php($groups = \Modules\Admission\Blocks\HighlightsBlock::groups((array) $data))
<div class="block block--admission-highlights">
    @foreach ($groups as $group)
        @if ($group['category'])
            <h3 style="margin-top:var(--space-6, 24px)">{{ $group['category']->name }}</h3>
        @endif
        <ul style="list-style:none;padding:0;display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(220px,1fr))">
            @foreach ($group['items'] as $item)
                <li class="card" style="display:flex;gap:var(--space-3, 12px);align-items:center;padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius-md, 8px);background:var(--color-surface)">
                    @if ($image = $item->firstMedia('image')?->url('thumb'))
                        <img src="{{ $image }}" alt="" loading="lazy" width="48" height="48" style="width:48px;height:48px;object-fit:contain">
                    @endif
                    @if ($item->url)
                        <a href="{{ $item->url }}" @if ($item->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $item->title }}</a>
                    @else
                        <span>{{ $item->title }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endforeach
</div>
