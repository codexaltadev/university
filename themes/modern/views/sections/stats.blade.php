{{-- Figures (theme section, source core.statistics: a statistics set). Contract: $section (title, subheading, content: eyebrow), $items ([label, value, display]) --}}
@if (collect($items ?? [])->isNotEmpty())
    <div class="container">
        <x-theme::section-head :eyebrow="$section->content['eyebrow'] ?? null" :title="$section->title" :sub="$section->subheading" />
        <dl class="stats m-0">
            @foreach ($items as $stat)
                <div class="stat flex flex-col-reverse">
                    <dt class="stat__label">{{ $stat['label'] }}</dt>
                    <dd class="m-0"><span class="stat__value">{{ $stat['display'] ?? $stat['value'] }}</span></dd>
                </div>
            @endforeach
        </dl>
    </div>
@endif
