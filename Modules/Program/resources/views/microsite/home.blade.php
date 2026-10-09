{{--
    A Program's page / microsite home until it builds its own (FR-PROG-13): hero with key facts and
    Apply / brochure buttons, overview, statistics, objective, eligibility, then the tabs other modules
    add (Curriculum, Tuition … — FR-PROG-18; an inactive module's tab simply is not there, AC-PROG-02).
    Majors, Options, Career / Industries, Facilitators, Materials and Memberships come from
    program::microsite.items; News / Events / Notices / FAQ from
    those modules' homepage sections once the Program builds its homepage.
    Contract: $entity (Program), $context
--}}
@php
    $image = $entity->firstMedia('image');
    $brochure = $entity->firstMedia('brochure');
    $stats = app(\Platform\Statistics\Statistics::class)->forOwner($entity->getMorphClass().':'.$entity->getKey());
    $tabs = app(\Platform\Website\EntityTabs::class)->render($entity);
    $department = $entity->department;
    $facts = array_filter([
        'level' => $entity->level?->name,
        'degree' => filled($entity->degree_name) ? $entity->degree_name : null,
        'duration' => $entity->durationText(),
        'credits' => $entity::plainNumber($entity->total_credits),
        'shift' => $entity->shiftLabel(),
        'mode' => $entity->classModeLabel(),
        'medium' => $entity->medium,
    ]);
@endphp
<section class="home-section program-hero" @if ($image) style="background:linear-gradient(rgba(0,0,0,.5), rgba(0,0,0,.5)), url('{{ $image->url('large') }}') center/cover;color:#fff" @endif>
    <div class="container">
        <span style="font-size:var(--text-sm, 16px)">{{ $entity->short_name }}@if ($department) · <a href="{{ $department->url() }}" @if ($image) style="color:inherit" @endif>{{ $department->name }}</a>@endif</span>
        <h1 @if ($image) style="color:inherit" @endif>{{ $entity->name }}</h1>
        @if (filled($entity->subtitle)) <p style="font-size:var(--text-lg, 20px)">{{ $entity->subtitle }}</p> @endif
        @if ($facts !== [])
            <dl class="program-facts" style="display:flex;flex-wrap:wrap;gap:var(--space-6, 24px);margin:var(--space-4, 16px) 0">
                @foreach ($facts as $key => $value)
                    <div><dt style="font-size:var(--text-xs, 14px);opacity:.8">{{ __("program::program.facts.{$key}") }}</dt><dd style="margin:0;font-weight:700">{{ $value }}</dd></div>
                @endforeach
            </dl>
        @endif
        <div style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:center">
            @if ($entity->application_url)
                <a href="{{ $entity->application_url }}" class="btn btn-primary" target="_blank" rel="noopener">{{ __('program::program.apply') }}</a>
            @endif
            @if ($brochure)
                <a href="{{ $brochure->url() }}" class="btn" download>{{ __('program::program.brochure') }}</a>
            @endif
            @if ($entity->video_url)
                <a href="{{ $entity->video_url }}" class="btn" target="_blank" rel="noopener">{{ __('program::program.video') }}</a>
            @endif
            @if ($entity->next_intake_on)
                <span>{{ __('program::program.next_intake') }}: <strong>{{ $entity->next_intake_on->translatedFormat('j F Y') }}</strong></span>
            @endif
        </div>
    </div>
</section>

@if ($tabs !== [])
    <nav class="program-tabs container" aria-label="{{ $entity->short_name }}" style="display:flex;flex-wrap:wrap;gap:1rem;padding-block:var(--space-4, 16px)">
        @if (filled($entity->summary)) <a href="#overview">{{ __('program::program.overview') }}</a> @endif
        @if (filled($entity->eligibility_general) || filled($entity->eligibility_english_medium)) <a href="#eligibility">{{ __('program::program.eligibility') }}</a> @endif
        @foreach ($tabs as $tab)
            <a href="#{{ str_replace('.', '-', $tab['key']) }}">{{ $tab['label'] }}</a>
        @endforeach
    </nav>
@endif

@if (filled($entity->summary))
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => 'overview', 'title' => __('program::program.overview'), 'subheading' => null, 'style' => null])">
        <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $entity->summary) !!}</div>
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

@if (filled($entity->objective))
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => 'objective', 'title' => __('program::program.objective'), 'subheading' => null, 'style' => null])">
        <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $entity->objective) !!}</div>
    </x-theme::section>
@endif

@include('program::microsite.items', ['part' => 'majors'])
@include('program::microsite.items', ['part' => 'variants'])

@if (filled($entity->eligibility_general) || filled($entity->eligibility_english_medium))
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => 'eligibility', 'title' => __('program::program.eligibility'), 'subheading' => null, 'style' => 'alt'])">
        <div style="display:grid;gap:var(--space-6, 24px);grid-template-columns:repeat(auto-fit, minmax(280px, 1fr))">
            @if (filled($entity->eligibility_general))
                <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $entity->eligibility_general) !!}</div>
            @endif
            @if (filled($entity->eligibility_english_medium))
                <div class="prose">
                    <h3>{{ __('program::program.eligibility_english_medium') }}</h3>
                    {!! \Illuminate\Support\Str::sanitizeHtml((string) $entity->eligibility_english_medium) !!}
                </div>
            @endif
        </div>
    </x-theme::section>
@endif

@include('program::microsite.items', ['part' => 'careers'])
@include('program::microsite.items', ['part' => 'people'])

@foreach ($tabs as $tab)
    <x-theme::section :section="new \Illuminate\Support\Fluent(['anchor' => str_replace('.', '-', $tab['key']), 'title' => $tab['label'], 'subheading' => null, 'style' => $loop->odd ? null : 'alt'])">
        {!! $tab['html'] !!}
    </x-theme::section>
@endforeach
