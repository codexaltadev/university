{{--
    /faculty-members/{slug} (FR-FM-04). Contract: $member (FacultyMember: profileSection(), links, biography, research_interests),
    $person (PersonPresenter: public contacts only; protectedEmailLink()), $departments (visible, primary first),
    $places (list of [place, url, role] — current public assignments)
--}}
<x-theme::layout :title="$person->name()">
    <div class="container" style="padding-block:var(--space-8, 32px)">
        <nav class="breadcrumb" aria-label="{{ __('platform::relations.breadcrumb') }}">
            <ol>
                <li><a href="{{ url('/') }}">{{ __('platform::relations.home') }}</a><span aria-hidden="true"> › </span></li>
                <li><a href="{{ url('/'.\Modules\FacultyMember\Models\FacultyMember::PATH) }}">{{ __('facultymember::facultymember.plural') }}</a><span aria-hidden="true"> › </span></li>
                <li><span aria-current="page">{{ $person->name() }}</span></li>
            </ol>
        </nav>

        <div style="display:flex;flex-wrap:wrap;gap:var(--space-6, 24px);align-items:flex-start;margin-top:var(--space-4, 16px)">
            @if ($photo = $person->photoUrl())
                <img src="{{ $photo }}" alt="{{ $person->name() }}" style="width:200px;height:200px;border-radius:var(--radius-md, 8px);object-fit:cover">
            @endif
            <div style="flex:1;min-width:240px">
                <h1 style="margin-top:0">{{ $person->name() }}</h1>
                @if ($person->designation())<p class="lead">{{ $person->designation() }}</p>@endif
                @if ($member->isOnLeave())<p><span class="badge">{{ __('facultymember::facultymember.employment.on_leave') }}</span></p>@endif
                @if ($departments->isNotEmpty())
                    <p>
                        @foreach ($departments as $department)
                            @if (! $loop->first) · @endif
                            @if ($url = $department->url())<a href="{{ $url }}">{{ $department->name }}</a>@else{{ $department->name }}@endif
                        @endforeach
                    </p>
                @endif
                <ul style="list-style:none;padding:0">
                    @if ($email = $person->protectedEmailLink())<li>{{ __('facultymember::facultymember.fields.email') }}: {{ $email }}</li>@endif
                    @if ($phone = $person->phone())<li>{{ __('facultymember::facultymember.fields.phone') }}: <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a>@if ($person->extension()) ({{ __('facultymember::facultymember.ext') }} {{ $person->extension() }})@endif</li>@endif
                    @if ($member->office_room)<li>{{ __('facultymember::facultymember.fields.office_room') }}: {{ $member->office_room }}</li>@endif
                </ul>
                @if (filled($member->links))
                    <p style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px)">
                        @foreach ($member->links as $key => $link)
                            <a href="{{ $link }}" rel="noopener" target="_blank">{{ __("facultymember::facultymember.links.{$key}") }}</a>
                        @endforeach
                    </p>
                @endif
            </div>
        </div>

        @if (filled($member->biography))
            <section style="margin-top:var(--space-8, 32px)">
                <h2>{{ __('facultymember::facultymember.fields.biography') }}</h2>
                <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $member->biography) !!}</div>
            </section>
        @endif

        @if (filled($member->research_interests))
            <section style="margin-top:var(--space-8, 32px)">
                <h2>{{ __('facultymember::facultymember.fields.research_interests') }}</h2>
                <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $member->research_interests) !!}</div>
            </section>
        @endif

        @foreach (\Modules\FacultyMember\Models\FacultyMember::PROFILE_SECTIONS as $section)
            @if ($items = $member->profileSection($section))
                <section style="margin-top:var(--space-8, 32px)">
                    <h2>{{ __("facultymember::facultymember.sections.{$section}") }}</h2>
                    <ul>
                        @foreach ($items as $item)
                            <li>
                                <strong>@if (! empty($item['url']))<a href="{{ $item['url'] }}" rel="noopener" target="_blank">{{ $item['title'] }}</a>@else{{ $item['title'] }}@endif</strong>
                                @if (! empty($item['institution'])) — {{ $item['institution'] }}@endif
                                @if (! empty($item['start_year']) || ! empty($item['end_year']))
                                    <span style="color:var(--color-muted, #6b7280)">({{ $item['start_year'] ?? '' }}@if (! empty($item['start_year']) && ! empty($item['end_year']) && $item['start_year'] !== $item['end_year'])–{{ $item['end_year'] }}@elseif (empty($item['start_year'])){{ $item['end_year'] }}@endif)</span>
                                @endif
                                @if (! empty($item['description']))<br><span>{{ $item['description'] }}</span>@endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endforeach

        @if ($places->isNotEmpty())
            <section style="margin-top:var(--space-8, 32px)">
                <h2>{{ __('facultymember::facultymember.sections.places') }}</h2>
                <ul>
                    @foreach ($places as $place)
                        <li>@if ($place['url'])<a href="{{ $place['url'] }}">{{ $place['place'] }}</a>@else{{ $place['place'] }}@endif — {{ $place['role'] }}</li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-theme::layout>
