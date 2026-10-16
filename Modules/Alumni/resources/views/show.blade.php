{{-- /alumni/{slug} (FR-ALUM-03). Contract: $alumnus, $person (PersonPresenter: public contacts only) --}}
<x-theme::layout :title="$person->name()">
    <div class="container" style="padding-block:var(--space-8, 32px)">
        <nav class="breadcrumb" aria-label="{{ __('platform::relations.breadcrumb') }}">
            <ol>
                <li><a href="{{ url('/') }}">{{ __('platform::relations.home') }}</a><span aria-hidden="true"> › </span></li>
                <li><a href="{{ url('/'.\Modules\Alumni\Models\Alumnus::PATH) }}">{{ __('alumni::alumni.plural') }}</a><span aria-hidden="true"> › </span></li>
                <li><span aria-current="page">{{ $person->name() }}</span></li>
            </ol>
        </nav>

        <div style="display:flex;flex-wrap:wrap;gap:var(--space-6, 24px);align-items:flex-start;margin-top:var(--space-4, 16px)">
            @if ($photo = $person->photoUrl())
                <img src="{{ $photo }}" alt="{{ $person->name() }}" style="width:220px;height:220px;border-radius:var(--radius-md, 8px);object-fit:cover">
            @endif
            <div style="flex:1;min-width:240px">
                <h1 style="margin-top:0">{{ $person->name() }}@if ($alumnus->is_pride) <span class="badge badge-success">{{ __('alumni::alumni.pride_badge') }}</span>@endif</h1>
                @if ($headline = $alumnus->headline())<p class="lead">{{ $headline }}</p>@endif
                <p>
                    @if ($program = $alumnus->program()){{ $program->name }}@elseif ($department = $alumnus->department()){{ $department->name }}@endif
                    @if ($alumnus->batch) · {{ __('alumni::alumni.batch_n', ['batch' => $alumnus->batch]) }}@endif
                    @if ($alumnus->passing_year) · {{ __('alumni::alumni.passed', ['year' => $alumnus->passing_year]) }}@endif
                </p>
                <ul style="list-style:none;padding:0">
                    @if ($email = $person->email())<li><a href="mailto:{{ $email }}">{{ $email }}</a></li>@endif
                    @if ($phone = $person->phone())<li>{{ $phone }}</li>@endif
                    @if ($alumnus->show_address && filled($alumnus->office_address))<li>{{ $alumnus->office_address }}</li>@endif
                </ul>
                @if ($links = array_filter((array) $alumnus->social_links))
                    <p style="display:flex;gap:var(--space-3, 12px)">
                        @foreach (\Modules\Alumni\Models\Alumnus::SOCIAL as $network)
                            @if (filled($links[$network] ?? null) && preg_match('#^https?://#i', $links[$network]))
                                <a href="{{ $links[$network] }}" target="_blank" rel="noopener nofollow">{{ __("alumni::alumni.social.{$network}") }}</a>
                            @endif
                        @endforeach
                    </p>
                @endif
            </div>
        </div>

        @if ($embed = $alumnus->videoEmbed())
            <div style="position:relative;padding-top:56.25%;margin-top:var(--space-6, 24px)">
                <iframe src="{{ $embed }}" title="{{ __('alumni::alumni.video') }}" loading="lazy" allowfullscreen style="position:absolute;inset:0;width:100%;height:100%;border:0"></iframe>
            </div>
        @endif

        @if (filled($alumnus->story))
            <section style="margin-top:var(--space-8, 32px)">
                <h2>{{ __('alumni::alumni.fields.story') }}</h2>
                <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $alumnus->story) !!}</div>
            </section>
        @endif
    </div>
</x-theme::layout>
