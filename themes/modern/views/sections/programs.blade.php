{{-- Featured programs (theme section, source program.featured): cards with level and duration. Contract: $section (title, subheading, content: eyebrow, button_label, button_url), $items (Program) --}}
<div class="container">
    <x-theme::section-head :eyebrow="$section->content['eyebrow'] ?? null" :title="$section->title ?: __('theme::theme.sections.programs')" :sub="$section->subheading"
                           :link="$section->content['button_url'] ?? null" :link-label="$section->content['button_label'] ?? null" />
    <div class="grid-cards">
        @foreach (collect($items ?? []) as $program)
            <article class="card card--media card--link">
                <div class="card__media">
                    @if ($image = $program->firstMedia('image'))<img src="{{ $image->url('medium') }}" alt="" loading="lazy">@endif
                </div>
                <div class="card__body">
                    @if ($program->level)<span><span class="badge">{{ $program->level->name }}</span></span>@endif
                    <h3 class="card__title"><a href="{{ url($program->url() ?? '#') }}" class="stretched">{{ $program->name }}</a></h3>
                    <p class="card__meta">{{ collect([$program->durationText(), $program->department?->name])->filter()->implode(' · ') }}</p>
                </div>
            </article>
        @endforeach
    </div>
</div>
