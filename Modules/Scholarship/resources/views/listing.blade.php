{{--
    /scholarships (FR-FEE-10). Contract: $groups (list of [category: ?Term, scholarships]), $categories (the ones in use),
    $selected (?Term, from ?category=), $scholarships (all shown, with parts_count), $layout (cards / accordion),
    $intro, $outro (HTML from Settings › Scholarships, or null)
--}}
<x-theme::layout :title="__('scholarship::scholarship.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('scholarship::scholarship.plural') }}@if ($selected) — {{ $selected->name }}@endif</h1>
        @if ($intro)
            <div class="scholarship-intro prose">{!! \Illuminate\Support\Str::sanitizeHtml($intro) !!}</div>
        @else
            <p class="scholarship-intro">{{ __('scholarship::scholarship.intro') }}</p>
        @endif

        @if ($categories->isNotEmpty())
            <nav class="scholarship-categories" aria-label="{{ __('scholarship::scholarship.fields.category') }}" style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px);margin-top:var(--space-4, 16px)">
                <a href="{{ url('/scholarships') }}" @class(['chip', 'chip-active' => ! $selected])>{{ __('scholarship::scholarship.all') }}</a>
                @foreach ($categories as $category)
                    <a href="{{ url('/scholarships').'?category='.$category->slug() }}" @class(['chip', 'chip-active' => $selected?->is($category)])>{{ $category->name }}</a>
                @endforeach
            </nav>
        @endif

        @forelse ($groups as $group)
            <section class="scholarship-group" style="margin-top:var(--space-8, 32px)">
                @if ($groups->count() > 1 || $group['category'])
                    <h2>{{ $group['category']?->name ?? __('scholarship::scholarship.other') }}</h2>
                @endif
                @include('scholarship::partials.'.$layout, ['scholarships' => $group['scholarships']])
            </section>
        @empty
            <p style="margin-top:var(--space-8, 32px)">{{ __('scholarship::scholarship.none') }}</p>
        @endforelse

        @if ($outro)
            <div class="scholarship-outro prose" style="margin-top:var(--space-10, 40px)">{!! \Illuminate\Support\Str::sanitizeHtml($outro) !!}</div>
        @endif
    </div>
</x-theme::layout>
