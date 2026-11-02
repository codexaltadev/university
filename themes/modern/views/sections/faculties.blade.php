{{-- Faculties (theme section, source faculty.list): tiles with the short name. Contract: $section (title, subheading, content: eyebrow, button_label, button_url), $items (Faculty) --}}
<div class="container">
    <x-theme::section-head :eyebrow="$section->content['eyebrow'] ?? null" :title="$section->title ?: __('theme::theme.sections.faculties')" :sub="$section->subheading"
                           :link="$section->content['button_url'] ?? null" :link-label="$section->content['button_label'] ?? null" />
    <div class="grid-cards grid-cards--sm">
        @foreach (collect($items ?? []) as $faculty)
            <article class="card card--link tile">
                <span class="tile__icon" aria-hidden="true">{{ mb_substr($faculty->short_name ?: $faculty->name, 0, 2) }}</span>
                <h3 class="tile__title"><a href="{{ url($faculty->url() ?? '#') }}" class="stretched">{{ $faculty->name }}</a></h3>
                <span class="card__more mt-auto">{{ __('theme::theme.read_more') }}<x-theme::icon name="arrow-right" width="14" height="14" /></span>
            </article>
        @endforeach
    </div>
</div>
