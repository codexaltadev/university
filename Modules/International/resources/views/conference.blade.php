{{-- /international/conferences/{slug} (FR-INTL-05). Contract: $conference, $base --}}
<x-theme::layout :title="$conference->title">
    <article class="container" style="padding-block:var(--space-10, 40px);max-width:960px">
        <p><a href="{{ url($base.'/'.\Modules\International\Models\Conference::PATH) }}">← {{ __('international::international.conferences') }}</a></p>
        <h1>{{ $conference->title }}</h1>
        <p>
            @if ($conference->starts_on)<time datetime="{{ $conference->starts_on->toDateString() }}">{{ $conference->starts_on->translatedFormat('j F Y') }}@if ($conference->ends_on && ! $conference->ends_on->eq($conference->starts_on)) – {{ $conference->ends_on->translatedFormat('j F Y') }}@endif</time>@endif
            @if ($conference->venue) · {{ $conference->venue }}@endif
        </p>
        @if ($conference->organizer_label)<p>{{ __('international::international.fields.organizer_label') }}: {{ $conference->organizer_label }}</p>@endif
        @if ($image = $conference->firstMedia('image'))<img src="{{ $image->url('large') ?? $image->url() }}" alt="{{ $image->alt_text ?: $conference->title }}" style="width:100%;border-radius:var(--radius, 8px)">@endif
        <div class="prose" style="margin-top:var(--space-4, 16px)">{!! $conference->body !!}</div>
        @if ($conference->website_url)<p style="margin-top:var(--space-6, 24px)"><a class="button" href="{{ $conference->website_url }}" target="_blank" rel="noopener">{{ __('international::international.visit') }}</a></p>@endif
    </article>
</x-theme::layout>
