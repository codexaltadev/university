{{-- /csr and /csr/category/{slug} (FR-CSR-02). Contract: $category (or null), $children (['term','image'] list), $activities (paginator or null), $layout (cards / grid / timeline), $base;
     for a theme: $categories (all), $stories (paginator "stories": searched ?q=, category ?category=), $picked (?Term), $q, $featured (highlighted first, 3) --}}
@php($path = url($base.'/'.\Modules\Csr\Models\Activity::PATH))
<x-theme::layout :title="$category?->name ?? __('csr::csr.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        @if ($category)<p><a href="{{ $category->parent_id ? $path.'/category/'.$category->parent?->slug() : $path }}">← {{ $category->parent?->name ?? __('csr::csr.plural') }}</a></p>@endif
        <h1>{{ $category?->name ?? __('csr::csr.plural') }}</h1>
        @if ($category?->description)<p>{{ $category->description }}</p>@endif
        @if ($children)
            <div style="margin-top:var(--space-6, 24px);display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
                @foreach ($children as $child)
                    <a class="csr-category" href="{{ $path.'/category/'.$child['term']->slug() }}" style="display:block;border:1px solid var(--color-border);border-radius:var(--radius, 8px);overflow:hidden;color:inherit;text-decoration:none">
                        @if ($child['image'])<img src="{{ $child['image']->url('medium') ?? $child['image']->url() }}" alt="{{ $child['image']->alt_text ?: $child['term']->name }}" loading="lazy" style="width:100%;aspect-ratio:16/10;object-fit:cover">@endif
                        <div style="padding:var(--space-4, 16px)">
                            <h2 style="margin:0 0 4px;font-size:1.15rem">{{ $child['term']->name }}</h2>
                            @if ($child['term']->description)<p style="margin:0">{{ \Illuminate\Support\Str::limit(strip_tags($child['term']->description), 160) }}</p>@endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
        @if ($activities !== null)
            @if ($activities->isEmpty())
                @unless ($children)<p style="margin-top:var(--space-8, 32px)">{{ __('csr::csr.none') }}</p>@endunless
            @else
                <div style="margin-top:var(--space-8, 32px);{{ $layout === 'timeline' ? 'border-left:2px solid var(--color-border);padding-left:var(--space-6, 24px)' : 'display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax('.($layout === 'grid' ? '200px' : '280px').',1fr))' }}">
                    @foreach ($activities as $activity)
                        @include('csr::partials.card', ['activity' => $activity, 'base' => $base, 'layout' => $layout])
                    @endforeach
                </div>
                <div style="margin-top:var(--space-6, 24px)">{{ $activities->links() }}</div>
            @endif
        @endif
    </div>
</x-theme::layout>
