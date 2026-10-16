{{--
    /testimonials and /{entity}/testimonials (FR-TEST-02). Contract: $items (in order), $type (chosen or null),
    $types (types that have testimonials here), $layout (slider / grid / masonry / video), $base (microsite path or '')
--}}
<x-theme::layout :title="__('testimonials::testimonials.plural')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('testimonials::testimonials.plural') }}</h1>

        @if (count($types) > 1)
            <nav aria-label="{{ __('testimonials::testimonials.fields.type') }}" style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px);margin-top:var(--space-4, 16px)">
                <a href="{{ url($base.'/testimonials').(request('view') ? '?view='.request('view') : '') }}" @class(['button', 'button--outline' => $type !== null])>{{ __('testimonials::testimonials.all') }}</a>
                @foreach ($types as $t)
                    <a href="{{ url($base.'/testimonials').'?'.http_build_query(array_filter(['type' => $t, 'view' => request('view')])) }}" @class(['button', 'button--outline' => $type !== $t])>{{ __("testimonials::testimonials.types_plural.{$t}") }}</a>
                @endforeach
            </nav>
        @endif

        @if ($items->isEmpty())
            <p style="margin-top:var(--space-8, 32px)">{{ __('testimonials::testimonials.none') }}</p>
        @else
            <div style="margin-top:var(--space-6, 24px)">
                @include('testimonials::partials.list', ['items' => $items, 'layout' => $layout])
            </div>
        @endif
    </div>
</x-theme::layout>
