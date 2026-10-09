{{--
    A Department's microsite home until it builds its own (FR-DEPT-07): cover, about, statistics,
    Head's message, contact. Programs, Faculty Members, News, Events, Notices and Achievements
    sections come from those modules' homepage sections once the Department builds its homepage.
    Contract: $entity (Department), $context
--}}
@php
    $cover = $entity->firstMedia('cover');
    $stats = app(\Platform\Statistics\Statistics::class)->forOwner($entity->getMorphClass().':'.$entity->getKey());
    $head = $entity->people('head')->first();
    $faculty = $entity->faculty;
@endphp
<section class="home-section department-hero" @if ($cover) style="background:linear-gradient(rgba(0,0,0,.45), rgba(0,0,0,.45)), url('{{ $cover->url('large') }}') center/cover;color:#fff" @endif>
    <div class="container">
        <span style="font-size:var(--text-sm, 16px)">{{ $entity->short_name }}@if ($faculty) · <a href="{{ $faculty->url() }}" @if ($cover) style="color:inherit" @endif>{{ $faculty->name }}</a>@endif</span>
        <h1 @if ($cover) style="color:inherit" @endif>{{ $entity->name }}</h1>
    </div>
</section>

@if (filled($entity->about))
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => 'about', 'title' => filled($entity->about_title) ? $entity->about_title : __('department::department.about'), 'subheading' => null, 'style' => null])">
        <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $entity->about) !!}</div>
    </x-theme::section>
@endif

@if ($stats !== [])
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => 'statistics', 'title' => null, 'subheading' => null, 'style' => 'alt'])">
        <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));text-align:center">
            @foreach ($stats as $stat)
                <div>
                    <div style="font-size:var(--text-3xl, 36px);font-weight:800;color:var(--color-primary)">{{ $stat['display'] }}</div>
                    <div>{{ $stat['label'] }}</div>
                </div>
            @endforeach
        </div>
    </x-theme::section>
@endif

@if ($head || filled($entity->head_message))
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => 'head', 'title' => __('department::department.head_message'), 'subheading' => null, 'style' => null])">
        <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:minmax(0, 200px) 1fr;align-items:start">
            @if ($head)
                <div>
                    @if ($head->photoUrl())
                        <img src="{{ $head->photoUrl() }}" alt="{{ $head->name() }}" style="width:100%;border-radius:var(--radius-md, 8px)">
                    @endif
                    <strong style="display:block;margin-top:.5rem">{{ $head->name() }}</strong>
                    <span style="color:var(--color-text-muted)">{{ $head->designation() ?? __('department::department.head') }}</span>
                </div>
            @endif
            <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $entity->head_message) !!}</div>
        </div>
    </x-theme::section>
@endif

@if ($entity->email || $entity->phone || filled($entity->location) || $entity->external_url)
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => 'contact', 'title' => __('department::department.contact'), 'subheading' => null, 'style' => 'alt'])">
        <address style="font-style:normal;display:grid;gap:.25rem">
            @if (filled($entity->location)) <span>{{ $entity->location }}</span> @endif
            @if ($entity->phone) <a href="tel:{{ preg_replace('/[^0-9+]/', '', $entity->phone) }}">{{ $entity->phone }}</a> @endif
            @if ($entity->email) <a href="mailto:{{ $entity->email }}">{{ $entity->email }}</a> @endif
            @if ($entity->external_url) <a href="{{ $entity->external_url }}" target="_blank" rel="noopener">{{ __('department::department.visit_website') }} ↗</a> @endif
        </address>
    </x-theme::section>
@endif
