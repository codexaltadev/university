{{--
    A notice /notices/{slug} (UI/UX Part B §12.5): hero with date and reference, the text, attachment cards.
    Contract: $notice, $attachments, $base, $locale, $page (NoticePage: boardUrl, date, views, category, body, files)
--}}
<x-theme::layout :title="$notice->title">
    <x-theme::page-hero :title="$notice->title" :crumbs="[['label' => __('notice::notice.plural'), 'url' => $page->boardUrl()], ['label' => $notice->title]]">
        <div class="meta-row mt-4">
            @if ($notice->is_urgent)<span class="badge badge--danger">{{ __('theme::theme.sections.urgent') }}</span>@endif
            @if ($page->category())<span class="badge">{{ $page->category() }}</span>@endif
            <span><x-theme::icon name="calendar" width="16" height="16" />{{ $page->date() }}</span>
            @if ($notice->reference_no)<span>{{ __('notice::notice.fields.reference_no') }}: {{ $notice->reference_no }}</span>@endif
            <span><x-theme::icon name="eye" width="16" height="16" />{{ $page->views() }}</span>
        </div>
    </x-theme::page-hero>

    <div class="container page-body">
        <article class="article">
            @if ($body = $page->body())<div class="prose">{!! $body !!}</div>@endif
            @if ($files = $page->files())
                <h2 class="mt-10 mb-4 text-xl">{{ __('notice::notice.fields.attachments') }}</h2>
                <div class="grid gap-3">
                    @foreach ($files as $file)
                        <div class="card file-card">
                            <span class="file-card__name"><x-theme::icon name="file-text" />{{ $file['title'] }} <span class="badge badge--muted">{{ $file['ext'] }}</span></span>
                            <a href="{{ $file['url'] }}" class="btn btn--primary btn--sm" target="_blank" rel="noopener"><x-theme::icon name="download" width="16" height="16" />{{ __('notice::notice.download') }}</a>
                        </div>
                    @endforeach
                </div>
            @endif
            <p class="mt-10"><a href="{{ $page->boardUrl() }}" class="btn"><x-theme::icon name="arrow-left" width="16" height="16" />{{ __('notice::notice.plural') }}</a></p>
        </article>
    </div>
</x-theme::layout>
