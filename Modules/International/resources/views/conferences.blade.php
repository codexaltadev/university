{{-- /international/conferences (FR-INTL-05). Contract: $upcoming (soonest first), $past (newest first), $base --}}
<x-theme::layout :title="__('international::international.conferences')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('international::international.conferences') }}</h1>
        @if ($upcoming->isEmpty() && $past->isEmpty())<p style="margin-top:var(--space-8, 32px)">{{ __('international::international.none') }}</p>@endif
        @foreach (['upcoming' => $upcoming, 'past' => $past] as $key => $list)
            @if ($list->isNotEmpty())
                <h2 style="margin-top:var(--space-8, 32px)">{{ __('international::international.'.$key) }}</h2>
                <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
                    @foreach ($list as $conference)
                        @php($image = $conference->firstMedia('image'))
                        <article style="border:1px solid var(--color-border);border-radius:var(--radius, 8px);overflow:hidden">
                            @if ($image)<img src="{{ $image->url('medium') ?? $image->url() }}" alt="" loading="lazy" style="width:100%;aspect-ratio:16/9;object-fit:cover">@endif
                            <div style="padding:var(--space-4, 16px)">
                                @if ($conference->starts_on)<time datetime="{{ $conference->starts_on->toDateString() }}" style="font-size:.85rem;opacity:.8">{{ $conference->starts_on->translatedFormat('j M Y') }}@if ($conference->ends_on && ! $conference->ends_on->eq($conference->starts_on)) – {{ $conference->ends_on->translatedFormat('j M Y') }}@endif</time>@endif
                                <h3 style="margin:4px 0;font-size:1.1rem"><a href="{{ url($base.'/'.\Modules\International\Models\Conference::PATH.'/'.$conference->slug()) }}">{{ $conference->title }}</a></h3>
                                @if ($conference->venue)<p style="margin:0">{{ $conference->venue }}</p>@endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>
</x-theme::layout>
