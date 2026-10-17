{{--
    /downloads/{slug} (FR-DOC-03/05/06). Contract: $document, $reader (PDF address for the preview / flipbook, or null),
    $download (counted download address, or null when read-only), $older (older versions), $base (microsite path or '')
--}}
<x-theme::layout :title="$document->title">
    <article class="container" style="padding-block:var(--space-10, 40px)">
        <p><a href="{{ url($base.'/'.\Modules\Documents\Models\Document::PATH) }}">← {{ __('documents::documents.plural') }}</a></p>
        <small style="text-transform:uppercase;letter-spacing:.04em;color:var(--color-muted, #6b7280)">{{ __("documents::documents.types.{$document->document_type}") }}</small>
        <h1 style="margin-top:var(--space-1, 4px)">{{ $document->title }}</h1>

        @if ($document->supersededBy && $document->supersededBy->isFrontendVisible())
            <p role="note" style="padding:var(--space-3, 12px);border-radius:var(--radius-sm, 4px);background:color-mix(in srgb, var(--color-accent, #39B54A) 15%, transparent)">
                {{ __('documents::documents.superseded_note') }} <a href="{{ url($document->supersededBy->url()) }}">{{ $document->supersededBy->title }}@if ($document->supersededBy->version_label) ({{ $document->supersededBy->version_label }})@endif</a>
            </p>
        @endif

        <dl style="display:grid;grid-template-columns:max-content 1fr;gap:var(--space-1, 4px) var(--space-4, 16px)">
            @if ($document->version_label)<dt>{{ __('documents::documents.fields.version_label') }}</dt><dd style="margin:0">{{ $document->version_label }}</dd>@endif
            @if ($document->approved_on)<dt>{{ __('documents::documents.fields.approved_on') }}</dt><dd style="margin:0">{{ $document->approved_on->translatedFormat('j F Y') }}</dd>@endif
            @if ($document->effective_on)<dt>{{ __('documents::documents.fields.effective_on') }}</dt><dd style="margin:0">{{ $document->effective_on->translatedFormat('j F Y') }}</dd>@endif
            @if ($document->session_label)<dt>{{ __('documents::documents.fields.session_label') }}</dt><dd style="margin:0">{{ $document->session_label }}</dd>@endif
            @if ($program = $document->program())<dt>{{ __('documents::documents.fields.program') }}</dt><dd style="margin:0"><a href="{{ url($program->url()) }}">{{ $program->name }}</a></dd>@endif
            @if ($facts = $document->fileFacts())<dt>{{ __('documents::documents.file') }}</dt><dd style="margin:0">{{ $facts }}</dd>@endif
        </dl>
        @if ($document->description)<p style="margin-top:var(--space-4, 16px)">{{ $document->description }}</p>@endif

        <div style="margin-top:var(--space-6, 24px)">
            @if ($reader && $document->flipbook())
                @include('documents::partials.flipbook', ['src' => $reader, 'title' => $document->title, 'download' => $download])
            @elseif ($reader)
                <iframe src="{{ $reader }}#view=FitH" title="{{ $document->title }}" loading="lazy" style="width:100%;height:80vh;border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px)"></iframe>
            @endif
            @if ($download && ! $document->flipbook())
                <p style="margin-top:var(--space-4, 16px)"><a class="btn" href="{{ $download }}">{{ __('documents::documents.download') }}</a></p>
            @endif
        </div>

        @if ($older->isNotEmpty())
            <section style="margin-top:var(--space-8, 32px)">
                <h2>{{ __('documents::documents.older_versions') }}</h2>
                <ul>
                    @foreach ($older as $old)
                        <li><a href="{{ url($old->url()) }}">{{ $old->version_label ?: $old->title }}</a>@if ($old->approved_on) — {{ $old->approved_on->translatedFormat('j F Y') }}@endif</li>
                    @endforeach
                </ul>
            </section>
        @endif
    </article>
</x-theme::layout>
