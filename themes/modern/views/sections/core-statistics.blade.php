{{-- Core statistics (C-20, core.statistics): the set's figures; display counter / cards / bar / icons. Contract: $items ([label, value, display, icon]), $section, $settings (display) --}}
@if (collect($items ?? [])->isNotEmpty())
    <div class="container">
        <x-theme::section-head :title="$section->title" :sub="$section->subheading" />
        <dl @class(['stats m-0', 'stats--cards' => in_array($settings['display'] ?? 'counter', ['cards', 'icons'], true)])>
            @foreach ($items as $stat)
                <div class="stat flex flex-col-reverse gap-1">
                    <dt class="stat__label">{{ $stat['label'] }}</dt>
                    <dd class="m-0">
                        @if (($settings['display'] ?? null) === 'icons' && ($stat['icon'] ?? null))<span class="tile__icon mb-3"><x-theme::icon :name="$stat['icon']" /></span>@endif
                        <span class="stat__value">{{ $stat['display'] ?? $stat['value'] }}</span>
                    </dd>
                </div>
            @endforeach
        </dl>
    </div>
@endif
