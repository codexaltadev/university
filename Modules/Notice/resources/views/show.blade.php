{{--
    /notices/{slug} (FR-NOT-01/05, AC-NOT-01). Contract: $notice, $attachments (MediaUsage list: ->item, ->meta['title'][locale]),
    $base (place path or ''), $locale
--}}
<x-theme::layout :title="$notice->title">
    <article class="notice" style="padding-block:var(--space-8, 32px)">
        <div class="container" style="max-width:900px">
            <nav class="breadcrumb" aria-label="{{ __('platform::relations.breadcrumb') }}">
                <ol>
                    <li><a href="{{ url($base ?: '/') }}">{{ __('platform::relations.home') }}</a><span aria-hidden="true"> › </span></li>
                    <li><a href="{{ url($base.'/'.\Modules\Notice\Models\Notice::PATH) }}">{{ __('notice::notice.plural') }}</a><span aria-hidden="true"> › </span></li>
                    <li><span aria-current="page">{{ $notice->title }}</span></li>
                </ol>
            </nav>

            <h1>{{ $notice->title }}</h1>
            <p style="color:var(--color-muted, #6b7280)">
                {{ $notice->active_at?->translatedFormat('j F Y') }}
                @if ($notice->reference_no) · {{ __('notice::notice.fields.reference_no') }}: {{ $notice->reference_no }}@endif
                @foreach ($notice->termsIn(\Modules\Notice\Models\Notice::CATEGORY) as $term) · {{ $term->name }}@endforeach
                @include('notice::partials.badges', ['notice' => $notice])
            </p>

            @if (filled($notice->body))
                <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $notice->body) !!}</div>
            @endif

            @if ($attachments->isNotEmpty())
                <section class="notice-files" style="margin-top:var(--space-8, 32px)">
                    <h2>{{ __('notice::notice.fields.attachments') }}</h2>
                    <ul style="list-style:none;padding:0;display:grid;gap:var(--space-4, 16px)">
                        @foreach ($attachments as $usage)
                            @php($title = ($usage->meta['title'][$locale] ?? null) ?: (collect((array) ($usage->meta['title'] ?? []))->filter()->first() ?: ($usage->item->title ?: $usage->item->fileName())))
                            @php($pdf = str_ends_with(strtolower((string) $usage->item->fileName()), '.pdf'))
                            <li style="padding:var(--space-3, 12px);border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px)" x-data="{ open: false }">
                                <div style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px);justify-content:space-between;align-items:center">
                                    <strong>{{ $title }}</strong>
                                    <span style="display:flex;gap:var(--space-2, 8px)">
                                        @if ($pdf)<button type="button" class="button" x-on:click="open = ! open" x-text="open ? @js(__('notice::notice.hide_preview')) : @js(__('notice::notice.preview'))">{{ __('notice::notice.preview') }}</button>@endif
                                        <a href="{{ $usage->item->url() }}" class="button button-primary" download>{{ __('notice::notice.download') }}</a>
                                    </span>
                                </div>
                                @if ($pdf)
                                    <template x-if="open"><iframe src="{{ $usage->item->url() }}" title="{{ $title }}" style="width:100%;height:70vh;border:0;margin-top:var(--space-3, 12px)"></iframe></template>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </article>
</x-theme::layout>
