{{-- /facilities and /{entity}/facilities (FR-FCL-02). Contract: $facilities (in order), $categories, $category, $layout (icons / cards / list), $base --}}
<x-theme::layout :title="__('facilities::facilities.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('facilities::facilities.plural') }}</h1>
        @if ($categories->count() > 1)
            <nav style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px);margin-top:var(--space-4, 16px)">
                <a href="{{ url($base.'/'.\Modules\Facilities\Models\Facility::PATH) }}" @class(['btn', 'btn--outline' => $category !== null])>{{ __('facilities::facilities.all') }}</a>
                @foreach ($categories as $c)
                    <a href="{{ url($base.'/'.\Modules\Facilities\Models\Facility::PATH).'?category='.$c->slug() }}" @class(['btn', 'btn--outline' => ! $category?->is($c)])>{{ $c->name }}</a>
                @endforeach
            </nav>
        @endif
        @if ($facilities->isEmpty())
            <p style="margin-top:var(--space-8, 32px)">{{ __('facilities::facilities.none') }}</p>
        @else
            <div style="margin-top:var(--space-6, 24px);{{ $layout === 'list' ? '' : 'display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax('.($layout === 'icons' ? '180px' : '260px').',1fr))' }}">
                @foreach ($facilities as $facility)
                    @include('facilities::partials.card', ['facility' => $facility, 'base' => $base, 'layout' => $layout])
                @endforeach
            </div>
        @endif
    </div>
</x-theme::layout>
