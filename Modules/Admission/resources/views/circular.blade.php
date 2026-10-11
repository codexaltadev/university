{{-- /admission/circulars/{slug} (FR-ADM-02/03). Contract: $circular --}}
<x-theme::layout :title="$circular->title">
    <div class="container" style="padding-block:var(--space-8, 32px);max-width:960px">
        <nav class="breadcrumb" aria-label="{{ __('platform::relations.breadcrumb') }}">
            <ol>
                <li><a href="{{ url('/') }}">{{ __('platform::relations.home') }}</a><span aria-hidden="true"> › </span></li>
                <li><a href="{{ url('/admission') }}">{{ __('admission::admission.title') }}</a><span aria-hidden="true"> › </span></li>
                <li><a href="{{ url('/'.\Modules\Admission\Models\Circular::PATH) }}">{{ __('admission::admission.circular.plural') }}</a><span aria-hidden="true"> › </span></li>
                <li><span aria-current="page">{{ $circular->title }}</span></li>
            </ol>
        </nav>

        <h1>{{ $circular->title }}</h1>
        <p>
            @if ($circular->session_label)<span>{{ $circular->session_label }}</span> · @endif
            <span class="badge {{ $circular->isClosed() ? 'badge-muted' : 'badge-success' }}">{{ __($circular->isClosed() ? 'admission::admission.closed' : 'admission::admission.open') }}</span>
        </p>
        @include('admission::partials.dates', ['circular' => $circular])

        @if (filled($circular->content))
            <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $circular->content) !!}</div>
        @endif

        <p style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px);margin-top:var(--space-6, 24px)">
            @if (! $circular->isClosed() && ($apply = settings('admission.apply_url')))
                <a href="{{ $apply }}" class="button button-primary" target="_blank" rel="noopener">{{ __('admission::admission.apply_now') }}</a>
            @endif
            @if ($pdf = $circular->firstMedia('pdf'))
                <a href="{{ $pdf->url() }}" class="button" target="_blank" rel="noopener">{{ __('admission::admission.fields.pdf') }}</a>
            @endif
        </p>
    </div>
</x-theme::layout>
