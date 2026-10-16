{{-- /leadership/{slug} (FR-LEAD-04). Contract: $term, $person (PersonPresenter), $position --}}
<x-theme::layout :title="$person->name()">
    <div class="container" style="padding-block:var(--space-8, 32px)">
        <nav class="breadcrumb" aria-label="{{ __('platform::relations.breadcrumb') }}">
            <ol>
                <li><a href="{{ url('/') }}">{{ __('platform::relations.home') }}</a><span aria-hidden="true"> › </span></li>
                <li><a href="{{ url('/'.\Modules\Leadership\Models\LeadershipTerm::PATH) }}">{{ __('leadership::leadership.plural') }}</a><span aria-hidden="true"> › </span></li>
                @if ($term->isFormer())
                    <li><a href="{{ url('/'.\Modules\Leadership\Models\LeadershipTerm::PATH.'/former') }}">{{ __('leadership::leadership.former') }}</a><span aria-hidden="true"> › </span></li>
                @endif
                <li><span aria-current="page">{{ $person->name() }}</span></li>
            </ol>
        </nav>

        <div style="display:flex;flex-wrap:wrap;gap:var(--space-6, 24px);align-items:flex-start;margin-top:var(--space-4, 16px)">
            @if ($photo = $person->photoUrl())
                <img src="{{ $photo }}" alt="{{ $person->name() }}" style="width:240px;height:240px;border-radius:var(--radius-md, 8px);object-fit:cover">
            @endif
            <div style="flex:1;min-width:240px">
                <h1 style="margin-top:0">{{ $person->name() }}</h1>
                <p class="lead">
                    {{ $term->isFormer() ? __('leadership::leadership.former_title', ['position' => $position->title]) : $position->title }}
                    @if ($tenure = $term->tenure()) <span style="color:var(--color-muted, #6b7280)">· {{ $tenure }}</span>@endif
                </p>
                @if ($profile = $person->profileUrl())
                    <p><a href="{{ $profile }}">{{ __('leadership::leadership.profile') }}</a></p>
                @endif
            </div>
        </div>

        @if (filled($term->message))
            <section class="leadership-message" style="margin-top:var(--space-8, 32px)">
                <h2>{{ __('leadership::leadership.fields.message') }}</h2>
                <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $term->message) !!}</div>
            </section>
        @endif
        @if (filled($term->biography))
            <section style="margin-top:var(--space-8, 32px)">
                <h2>{{ __('leadership::leadership.fields.biography') }}</h2>
                <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $term->biography) !!}</div>
            </section>
        @endif
    </div>
</x-theme::layout>
