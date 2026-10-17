{{-- /international. Contract: $counts (partners, countries, conferences, visitors), $featured (highlighted partners), $base --}}
@php($root = url($base.'/'.\Modules\International\Support\InternationalWebsite::ROOT))
<x-theme::layout :title="__('international::international.title')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('international::international.title') }}</h1>
        <div style="margin-top:var(--space-6, 24px);display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
            @foreach ([['partners', $counts['partners'], '/partners'], ['countries', $counts['countries'], '/partners?view=countries'], ['conferences', $counts['conferences'], '/conferences'], ['visitors', $counts['visitors'], '/visitors']] as [$key, $count, $path])
                @if ($count > 0)
                    <a href="{{ $root.$path }}" style="display:block;padding:var(--space-6, 24px);border:1px solid var(--color-border);border-radius:var(--radius, 8px);text-align:center;color:inherit;text-decoration:none">
                        <strong style="display:block;font-size:2rem;color:var(--color-primary)">{{ number_format($count) }}</strong>
                        <span>{{ __('international::international.'.$key) }}</span>
                    </a>
                @endif
            @endforeach
        </div>
        @if ($featured->isNotEmpty())
            <h2 style="margin-top:var(--space-10, 40px)">{{ __('international::international.featured') }}</h2>
            <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(180px,1fr))">
                @foreach ($featured as $institution)
                    @include('international::partials.institution', ['institution' => $institution])
                @endforeach
            </div>
        @endif
    </div>
</x-theme::layout>
