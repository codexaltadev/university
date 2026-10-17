{{-- /scholarships/{slug} (FR-FEE-10). Contract: $scholarship, $parent (or null), $parts (open first) --}}
<x-theme::layout :title="$scholarship->title">
    <div class="container" style="padding-block:var(--space-8, 32px);max-width:var(--container-max, 1280px)">
        <nav class="breadcrumb" aria-label="{{ __('platform::relations.breadcrumb') }}">
            <ol>
                <li><a href="{{ url('/') }}">{{ __('platform::relations.home') }}</a><span aria-hidden="true"> › </span></li>
                <li><a href="{{ url('/scholarships') }}">{{ __('scholarship::scholarship.plural') }}</a><span aria-hidden="true"> › </span></li>
                @if ($parent)
                    <li><a href="{{ $parent->url() }}">{{ $parent->title }}</a><span aria-hidden="true"> › </span></li>
                @endif
                <li><span aria-current="page">{{ $scholarship->title }}</span></li>
            </ol>
        </nav>

        <h1>{{ $scholarship->title }}</h1>
        @if ($scholarship->deadline_at)
            <p class="scholarship-deadline">
                <strong>{{ __('scholarship::scholarship.fields.deadline_at') }}:</strong> {{ $scholarship->deadline_at->translatedFormat('d F Y') }}
                <span class="badge {{ $scholarship->isClosed() ? 'badge--muted' : 'badge--success' }}">{{ $scholarship->isClosed() ? __('scholarship::scholarship.closed') : __('scholarship::scholarship.open') }}</span>
            </p>
        @endif
        @if ($image = $scholarship->firstMedia('image')?->url('large'))
            <img src="{{ $image }}" alt="" style="width:100%;max-height:420px;object-fit:cover;border-radius:var(--radius-md, 8px);margin-block:var(--space-4, 16px)">
        @endif
        @if ($scholarship->summary)
            <p class="lead">{{ $scholarship->summary }}</p>
        @endif

        @foreach (['details', 'eligibility', 'how_to_apply'] as $part)
            @if (filled($scholarship->{$part}))
                <section class="scholarship-{{ str_replace('_', '-', $part) }}" style="margin-top:var(--space-6, 24px)">
                    @unless ($part === 'details')
                        <h2>{{ __("scholarship::scholarship.fields.{$part}") }}</h2>
                    @endunless
                    <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $scholarship->{$part}) !!}</div>
                </section>
            @endif
        @endforeach

        @if ($scholarship->apply_url && ! $scholarship->isClosed())
            <p style="margin-top:var(--space-6, 24px)"><a href="{{ $scholarship->apply_url }}" class="btn btn--primary" target="_blank" rel="noopener">{{ __('scholarship::scholarship.apply') }}</a></p>
        @endif

        @if (($files = $scholarship->mediaIn('attachments'))->isNotEmpty())
            <section style="margin-top:var(--space-6, 24px)">
                <h2>{{ __('scholarship::scholarship.fields.attachments') }}</h2>
                <ul>
                    @foreach ($files as $file)
                        <li><a href="{{ $file->url() }}" target="_blank" rel="noopener">{{ $file->title ?: $file->fileName() }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($parts->isNotEmpty())
            <section class="scholarship-parts" style="margin-top:var(--space-8, 32px)">
                <h2>{{ __('scholarship::scholarship.opportunities') }}</h2>
                <ul style="list-style:none;padding:0;display:grid;gap:var(--space-3, 12px)">
                    @foreach ($parts as $item)
                        <li style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:var(--space-3, 12px);padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px);{{ $item->isClosed() ? 'opacity:.7' : '' }}">
                            <a href="{{ $item->url() }}">{{ $item->title }}</a>
                            @if ($item->deadline_at)
                                <span>{{ $item->deadline_at->translatedFormat('d M Y') }} · {{ $item->isClosed() ? __('scholarship::scholarship.closed') : __('scholarship::scholarship.open') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-theme::layout>
