{{--
    A lab's microsite home until it builds its own (FR-LAB-06): cover, about, statistics, scopes,
    equipment, projects, products, in-charge and team, contact. News, events, gallery, slider and
    partners come from those modules' homepage sections once the lab builds its homepage.
    Contract: $entity (Lab), $context
--}}
@php
    $cover = $entity->firstMedia('cover');
    $logo = $entity->firstMedia('logo');
    $base = rtrim((string) $entity->url(), '/');
    $stats = app(\Platform\Statistics\Statistics::class)->forOwner($entity->getMorphClass().':'.$entity->getKey());
    $inCharge = $entity->people('in_charge')->first();
    $team = $entity->people('team');
    $items = $entity->items()->where('is_active', true)->get()->groupBy('item_type');
    $type = $entity->termsIn(\Modules\Lab\Models\Lab::TYPE)->first();
    $section = fn (string $anchor, ?string $title, ?string $style = null) => new \Illuminate\Support\Fluent(['anchor' => $anchor, 'title' => $title, 'subheading' => null, 'style' => $style]);
@endphp
<section class="home-section lab-hero" @if ($cover) style="background:linear-gradient(rgba(0,0,0,.45), rgba(0,0,0,.45)), url('{{ $cover->url('large') }}') center/cover;color:#fff" @endif>
    <div class="container" style="display:flex;gap:var(--space-4, 16px);align-items:center">
        @if ($logo)<img src="{{ $logo->url('thumb') ?? $logo->url() }}" alt="" style="width:72px;height:72px;object-fit:contain;background:#fff;border-radius:var(--radius, 8px);padding:4px">@endif
        <div>
            @if ($type)<span style="font-size:var(--text-sm, 16px)">{{ $type->name }}</span>@endif
            <h1 @if ($cover) style="color:inherit" @endif>{{ $entity->name }}</h1>
            @if (filled($entity->summary))<p style="margin:0">{{ $entity->summary }}</p>@endif
        </div>
    </div>
</section>

@php($sameAsSummary = trim(html_entity_decode(strip_tags((string) $entity->body))) === trim((string) $entity->summary))
@if ((filled($entity->body) && ! $sameAsSummary) || $entity->videoEmbed())
    <x-theme::section :section="$section('about', __('lab::lab.about'))">
        <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fit,minmax(320px,1fr));align-items:start">
            @if (filled($entity->body) && ! $sameAsSummary)<div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $entity->body) !!}</div>@endif
            @if ($embed = $entity->videoEmbed())<div style="aspect-ratio:16/9"><iframe src="{{ $embed }}" title="{{ $entity->name }}" style="width:100%;height:100%;border:0" allowfullscreen loading="lazy"></iframe></div>@endif
        </div>
    </x-theme::section>
@endif

@if ($stats !== [])
    <x-theme::section :section="$section('statistics', null, 'alt')">
        <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));text-align:center">
            @foreach ($stats as $stat)
                <div><div style="font-size:var(--text-3xl, 36px);font-weight:800;color:var(--color-primary)">{{ $stat['display'] }}</div><div>{{ $stat['label'] }}</div></div>
            @endforeach
        </div>
    </x-theme::section>
@endif

@foreach (['scope', 'equipment', 'project', 'product'] as $i => $kind)
    @if (($items[$kind] ?? collect())->isNotEmpty())
        <x-theme::section :section="$section(\Modules\Lab\Models\LabItem::SEGMENTS[$kind], __('lab::lab.types_plural.'.$kind), $i % 2 ? null : 'alt')">
            @if ($kind === 'equipment' && filled($entity->equipment_summary))<div class="prose" style="margin-bottom:var(--space-4, 16px)">{!! \Illuminate\Support\Str::sanitizeHtml((string) $entity->equipment_summary) !!}</div>@endif
            <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
                @foreach ($items[$kind]->take(6) as $item)
                    @include('lab::partials.item', ['item' => $item])
                @endforeach
            </div>
            @if ($items[$kind]->count() > 6 || $kind !== 'scope')
                <p style="margin-top:var(--space-4, 16px)"><a class="button button--outline" href="{{ $base.'/'.\Modules\Lab\Models\LabItem::SEGMENTS[$kind] }}">{{ __('lab::lab.see_all') }}</a></p>
            @endif
        </x-theme::section>
    @endif
@endforeach

@if ($inCharge || $team->isNotEmpty())
    <x-theme::section :section="$section('team', __('lab::lab.team'))">
        <ul style="list-style:none;padding:0;display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(220px,1fr))">
            @foreach (collect([$inCharge])->filter()->concat($team)->unique(fn ($p) => $p->name()) as $person)
                <li style="text-align:center">
                    @if ($person->photoUrl())<img src="{{ $person->photoUrl() }}" alt="" loading="lazy" style="width:120px;height:120px;border-radius:50%;object-fit:cover">@endif
                    <strong style="display:block">{{ $person->name() }}</strong>
                    <span style="color:var(--color-text-muted)">{{ $loop->first && $inCharge ? __('lab::lab.in_charge') : $person->designation() }}</span>
                    @if ($person->email())<div><a href="mailto:{{ $person->email() }}">{{ $person->email() }}</a></div>@endif
                </li>
            @endforeach
        </ul>
    </x-theme::section>
@endif

@if ($entity->email || $entity->phone || filled($entity->location) || $entity->website_url)
    <x-theme::section :section="$section('contact', __('lab::lab.sections.contact'), 'alt')">
        <address style="font-style:normal;display:grid;gap:.25rem">
            @if (filled($entity->location))<span>{{ $entity->location }}</span>@endif
            @if ($entity->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $entity->phone) }}">{{ $entity->phone }}</a>@endif
            @if ($entity->email)<a href="mailto:{{ $entity->email }}">{{ $entity->email }}</a>@endif
            @if ($entity->website_url)<a href="{{ $entity->website_url }}" target="_blank" rel="noopener">{{ $entity->website_url }}</a>@endif
        </address>
    </x-theme::section>
@endif
