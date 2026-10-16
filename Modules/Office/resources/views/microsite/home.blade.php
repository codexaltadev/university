{{--
    An office's microsite home until it builds its own (FR-OFF-10): overview, statistics, Head's
    message, services, team, sections, contact. Notices and Documents come from those modules'
    homepage sections once the office builds its homepage.
    Contract: $entity (Office), $context
--}}
@php
    $cover = $entity->firstMedia('cover');
    $stats = app(\Platform\Statistics\Statistics::class)->forOwner($entity->getMorphClass().':'.$entity->getKey());
    $head = $entity->people('head')->first();
    $team = $entity->people('team');
    $services = $entity->services()->where('is_active', true)->get();
    $sections = $entity->sections()->where('is_active', true)->get();
@endphp
<section class="home-section office-hero" @if ($cover) style="background:linear-gradient(rgba(0,0,0,.45), rgba(0,0,0,.45)), url('{{ $cover->url('large') }}') center/cover;color:#fff" @endif>
    <div class="container">
        @if ($entity->type)<span style="font-size:var(--text-sm, 16px)">{{ $entity->type->name }}</span>@endif
        <h1 @if ($cover) style="color:inherit" @endif>{{ $entity->name }}</h1>
    </div>
</section>

@if (filled($entity->overview))
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => 'overview', 'title' => __('office::office.fields.overview'), 'subheading' => null, 'style' => null])">
        <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $entity->overview) !!}</div>
    </x-theme::section>
@endif

@if ($stats !== [])
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => 'statistics', 'title' => null, 'subheading' => null, 'style' => 'alt'])">
        <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));text-align:center">
            @foreach ($stats as $stat)
                <div><div style="font-size:var(--text-3xl, 36px);font-weight:800;color:var(--color-primary)">{{ $stat['display'] }}</div><div>{{ $stat['label'] }}</div></div>
            @endforeach
        </div>
    </x-theme::section>
@endif

@if ($head || filled($entity->head_message))
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => 'head', 'title' => __('office::office.fields.head_message'), 'subheading' => null, 'style' => null])">
        <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:minmax(0, 200px) 1fr;align-items:start">
            @if ($head)
                <div>
                    @if ($head->photoUrl())<img src="{{ $head->photoUrl() }}" alt="{{ $head->name() }}" style="width:100%;border-radius:var(--radius-md, 8px)">@endif
                    <strong style="display:block;margin-top:.5rem">{{ $head->name() }}</strong>
                    <span style="color:var(--color-text-muted)">{{ $head->designation() }}</span>
                </div>
            @endif
            <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $entity->head_message) !!}</div>
        </div>
    </x-theme::section>
@endif

@if ($services->isNotEmpty())
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => 'services', 'title' => __('office::office.services.plural'), 'subheading' => null, 'style' => 'alt'])">
        <div style="display:grid;gap:var(--space-2, 8px)">
            @foreach ($services as $service)
                @include('office::partials.service', ['service' => $service])
            @endforeach
        </div>
    </x-theme::section>
@endif

@if ($team->isNotEmpty())
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => 'team', 'title' => __('office::office.team'), 'subheading' => null, 'style' => null])">
        <ul style="list-style:none;padding:0;display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(220px,1fr))">
            @foreach ($team as $person)
                <li style="text-align:center">
                    @if ($person->photoUrl())<img src="{{ $person->photoUrl() }}" alt="" loading="lazy" style="width:120px;height:120px;border-radius:50%;object-fit:cover">@endif
                    <strong style="display:block">{{ $person->name() }}</strong>
                    <span style="color:var(--color-text-muted)">{{ $person->designation() }}</span>
                    @if ($person->email())<div><a href="mailto:{{ $person->email() }}">{{ $person->email() }}</a></div>@endif
                </li>
            @endforeach
        </ul>
    </x-theme::section>
@endif

@if ($sections->isNotEmpty())
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => 'sections', 'title' => __('office::office.sections_list.plural'), 'subheading' => null, 'style' => 'alt'])">
        <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
            @foreach ($sections as $section)
                <article class="card" style="padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius-md, 8px)">
                    <h3 style="margin-top:0">{{ $section->name }}</h3>
                    @if (filled($section->description))<p>{{ $section->description }}</p>@endif
                    @foreach ($section->people(\Modules\Office\Models\OfficeSection::ROLE) as $person)
                        <div>{{ $person->name() }}@if ($person->designation()) — {{ $person->designation() }}@endif</div>
                    @endforeach
                    @if ($section->email)<a href="mailto:{{ $section->email }}">{{ $section->email }}</a>@endif
                </article>
            @endforeach
        </div>
    </x-theme::section>
@endif

@if ($entity->email || $entity->phone || filled($entity->location) || filled($entity->office_hours))
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => 'contact', 'title' => __('office::office.sections.contact'), 'subheading' => null, 'style' => null])">
        <address style="font-style:normal;display:grid;gap:.25rem">
            @if (filled($entity->location))<span>{{ $entity->location }}</span>@endif
            @if (filled($entity->office_hours))<span>{{ __('office::office.fields.office_hours') }}: {{ $entity->office_hours }}</span>@endif
            @if ($entity->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $entity->phone) }}">{{ $entity->phone }}@if ($entity->extension) ({{ __('office::office.ext') }} {{ $entity->extension }})@endif</a>@endif
            @if ($entity->email)<a href="mailto:{{ $entity->email }}">{{ $entity->email }}</a>@endif
        </address>
    </x-theme::section>
@endif
