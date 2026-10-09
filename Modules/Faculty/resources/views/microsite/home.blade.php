{{--
    A Faculty's microsite home until it builds its own (FR-FAC-06, AC-FAC-01): cover, overview,
    statistics, Dean's message. Departments, Programs, News, Events and Notices sections come from
    those modules' homepage sections once the Faculty builds its homepage.
    Contract: $entity (Faculty), $context
--}}
@php
    $cover = $entity->firstMedia('cover');
    $stats = app(\Platform\Statistics\Statistics::class)->forOwner($entity->getMorphClass().':'.$entity->getKey());
    $dean = $entity->people('dean')->first();
@endphp
<section class="home-section faculty-hero" @if ($cover) style="background:linear-gradient(rgba(0,0,0,.45), rgba(0,0,0,.45)), url('{{ $cover->url('large') }}') center/cover;color:#fff" @endif>
    <div class="container">
        <span style="font-size:var(--text-sm, 16px)">{{ $entity->short_name }}</span>
        <h1 @if ($cover) style="color:inherit" @endif>{{ $entity->name }}</h1>
    </div>
</section>

@if (filled($entity->overview))
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => 'overview', 'title' => __('faculty::faculty.overview'), 'subheading' => null, 'style' => null])">
        <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $entity->overview) !!}</div>
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

@if ($dean || filled($entity->dean_message))
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => 'dean', 'title' => __('faculty::faculty.dean_message'), 'subheading' => null, 'style' => null])">
        <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:minmax(0, 200px) 1fr;align-items:start">
            @if ($dean)
                <div>
                    @if ($dean->photoUrl())
                        <img src="{{ $dean->photoUrl() }}" alt="{{ $dean->name() }}" style="width:100%;border-radius:var(--radius-md, 8px)">
                    @endif
                    <strong style="display:block;margin-top:.5rem">{{ $dean->name() }}</strong>
                    <span style="color:var(--color-text-muted)">{{ $dean->designation() ?? __('faculty::faculty.dean') }}</span>
                </div>
            @endif
            <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $entity->dean_message) !!}</div>
        </div>
    </x-theme::section>
@endif

@if ($entity->email || $entity->phone || filled($entity->location))
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => 'contact', 'title' => __('faculty::faculty.contact'), 'subheading' => null, 'style' => 'alt'])">
        <address style="font-style:normal;display:grid;gap:.25rem">
            @if (filled($entity->location)) <span>{{ $entity->location }}</span> @endif
            @if ($entity->phone) <a href="tel:{{ preg_replace('/[^0-9+]/', '', $entity->phone) }}">{{ $entity->phone }}</a> @endif
            @if ($entity->email) <a href="mailto:{{ $entity->email }}">{{ $entity->email }}</a> @endif
        </address>
    </x-theme::section>
@endif
