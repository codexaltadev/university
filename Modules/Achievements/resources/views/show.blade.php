{{--
    /achievements/{slug}. Contract: $achievement, $achievers (PersonView list, with their note), $gallery (MediaItem list),
    $post (related News post or null), $base (microsite path or '')
--}}
<x-theme::layout :title="$achievement->title">
    <article class="container" style="padding-block:var(--space-10, 40px);max-width:960px">
        <p><a href="{{ url($base.'/'.\Modules\Achievements\Models\Achievement::PATH) }}">← {{ __('achievements::achievements.plural') }}</a></p>
        <p style="margin:0;text-transform:uppercase;letter-spacing:.04em;font-size:.85em;color:var(--color-muted, #6b7280)">
            {{ __("achievements::achievements.levels.{$achievement->level}") }}@if ($achievement->position_label) · {{ $achievement->position_label }}@endif
        </p>
        <h1 style="margin-top:var(--space-1, 4px)">{{ $achievement->title }}</h1>
        <dl style="display:grid;grid-template-columns:max-content 1fr;gap:var(--space-1, 4px) var(--space-4, 16px)">
            <dt>{{ __('achievements::achievements.fields.achieved_on') }}</dt><dd style="margin:0">{{ $achievement->achieved_on->translatedFormat('j F Y') }}</dd>
            @if ($achievement->competition)<dt>{{ __('achievements::achievements.fields.competition') }}</dt><dd style="margin:0">{{ $achievement->competition }}</dd>@endif
            @if ($achievement->issuer)<dt>{{ __('achievements::achievements.fields.issuer') }}</dt><dd style="margin:0">{{ $achievement->issuer }}</dd>@endif
            @if ($achievement->team_name)<dt>{{ __('achievements::achievements.fields.team_name') }}</dt><dd style="margin:0">{{ $achievement->team_name }}</dd>@endif
        </dl>

        @if ($picture = $achievement->firstMedia('image'))
            <img src="{{ $picture->url('large') ?? $picture->url() }}" alt="{{ $picture->alt_text ?: $achievement->title }}" style="width:100%;margin-top:var(--space-6, 24px);border-radius:var(--radius, 8px)">
        @endif
        @if ($achievement->summary)<p style="font-size:1.1rem;margin-top:var(--space-5, 20px)">{{ $achievement->summary }}</p>@endif
        @if ($achievement->body)<div class="prose">{!! $achievement->body !!}</div>@endif

        @if ($achievers->isNotEmpty())
            <section style="margin-top:var(--space-8, 32px)">
                <h2>{{ __('achievements::achievements.sections.achievers') }}</h2>
                <ul style="list-style:none;padding:0;display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(220px,1fr))">
                    @foreach ($achievers as $person)
                        <li style="display:flex;gap:var(--space-3, 12px);align-items:center">
                            @if ($person->photoUrl())<img src="{{ $person->photoUrl() }}" alt="" loading="lazy" style="width:52px;height:52px;border-radius:50%;object-fit:cover">@endif
                            <span>
                                <strong>@if ($person->profileUrl())<a href="{{ $person->profileUrl() }}">{{ $person->name() }}</a>@else{{ $person->name() }}@endif</strong>
                                @if ($person->note() || $person->designation())<br><small>{{ $person->note() ?: $person->designation() }}</small>@endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($gallery->isNotEmpty())
            <section style="margin-top:var(--space-8, 32px)">
                <h2>{{ __('achievements::achievements.fields.gallery') }}</h2>
                @if (view()->exists('gallery::partials.photos'))
                    @include('gallery::partials.photos', ['photos' => $gallery->map(fn ($item) => ['item' => $item, 'caption' => $item->caption ?: null]), 'layout' => 'grid'])
                @else
                    <div style="display:grid;gap:var(--space-3, 12px);grid-template-columns:repeat(auto-fill,minmax(180px,1fr))">
                        @foreach ($gallery as $item)
                            <a href="{{ $item->url('large') ?? $item->url() }}" target="_blank" rel="noopener"><img src="{{ $item->url('medium') ?? $item->url() }}" alt="{{ $item->alt_text ?? '' }}" loading="lazy" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:var(--radius-sm, 4px)"></a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        @if ($post)
            <p style="margin-top:var(--space-8, 32px)"><a href="{{ url($post->url()) }}" class="btn btn--outline">{{ __('achievements::achievements.read_news') }}: {{ $post->title }}</a></p>
        @endif
    </article>
</x-theme::layout>
