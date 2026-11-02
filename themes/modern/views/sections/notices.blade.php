{{-- Notice board (theme section, source notice.board): date boxes, urgent in red. Contract: $section (title, subheading, content: eyebrow, button_label, button_url), $items (Notice) --}}
<div class="container">
    <x-theme::section-head :eyebrow="$section->content['eyebrow'] ?? null" :title="$section->title ?: __('theme::theme.sections.notices')" :sub="$section->subheading"
                           :link="$section->content['button_url'] ?? null" :link-label="$section->content['button_label'] ?? null" />
    @if (collect($items ?? [])->isEmpty())
        <p class="empty-state">{{ __('theme::theme.sections.nothing_yet') }}</p>
    @else
        <ul class="row-list">
            @foreach ($items as $notice)
                <li @class(['is-urgent' => $notice->is_urgent])>
                    <span class="date-box"><b>{{ $notice->active_at?->translatedFormat('d') }}</b>{{ $notice->active_at?->translatedFormat('M') }}</span>
                    <div class="min-w-0">
                        <a href="{{ url($notice->url()) }}" class="row-list__title stretched">{{ $notice->title }}</a>
                        @if ($notice->is_urgent)<span class="badge badge--danger">{{ __('theme::theme.sections.urgent') }}</span>@endif
                        @if ($notice->reference_no)<p class="row-list__meta">{{ $notice->reference_no }}</p>@endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
