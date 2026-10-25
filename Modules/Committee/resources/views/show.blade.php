{{--
    /committees/{slug} (FR-COM-02/05/07). Contract: $committee, $members (PersonView list in role order), $previous (earlier terms,
    newest first), $next (?Committee: the term after this one), $orderPdf (?MediaItem), $base (microsite path or ''),
    $committees (the running committees of this place)
--}}
<x-theme::layout :title="$committee->name">
    <div class="container" style="padding-block:var(--space-8, 32px)">
        <nav class="breadcrumb" aria-label="{{ __('platform::relations.breadcrumb') }}">
            <ol>
                <li><a href="{{ url($base ?: '/') }}">{{ __('platform::relations.home') }}</a><span aria-hidden="true"> › </span></li>
                <li><a href="{{ url($base.'/committees') }}">{{ __('committee::committee.plural') }}</a><span aria-hidden="true"> › </span></li>
                <li><span aria-current="page">{{ $committee->name }}</span></li>
            </ol>
        </nav>

        <h1>{{ $committee->name }}</h1>
        <p style="color:var(--color-muted, #6b7280)">
            {{ $committee->category?->name }}
            @if ($term = $committee->termLabel()) · {{ __('committee::committee.term') }}: {{ $term }}@endif
            @if ($committee->isPrevious()) <span class="badge badge--muted">{{ __('committee::committee.previous_term') }}</span>@endif
        </p>
        @if ($next)
            <p><a href="{{ url($base.'/committees/'.$next->slug()) }}">{{ __('committee::committee.see_current') }}</a></p>
        @endif
        @if (filled($committee->description))
            <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $committee->description) !!}</div>
        @endif

        <section style="margin-top:var(--space-8, 32px)">
            <h2>{{ __('committee::committee.members') }}</h2>
            @if ($members->isEmpty())
                <p>{{ __('committee::committee.no_members') }}</p>
            @else
                <div style="overflow-x:auto">
                    <table class="table" style="width:100%">
                        <thead><tr>
                            <th scope="col">#</th>
                            <th scope="col">{{ __('committee::committee.fields.name') }}</th>
                            <th scope="col">{{ __('committee::committee.role') }}</th>
                            <th scope="col">{{ __('committee::committee.contact') }}</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($members as $i => $person)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        @if ($url = $person->profileUrl())<a href="{{ $url }}">{{ $person->name() }}</a>@else{{ $person->name() }}@endif
                                        @if ($person->designation())<br><small>{{ $person->designation() }}</small>@endif
                                    </td>
                                    <td>{{ $person->roleLabel }}@if ($note = $person->note())<br><small>{{ $note }}</small>@endif</td>
                                    <td>
                                        @if ($phone = $person->phone()){{ $phone }}@endif
                                        @if ($email = $person->email())<br><a href="mailto:{{ $email }}">{{ $email }}</a>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if ($orderPdf)
            <p style="margin-top:var(--space-6, 24px)"><a href="{{ $orderPdf->url() }}" class="btn" target="_blank" rel="noopener">{{ __('committee::committee.download_order') }}</a></p>
        @endif

        @if ($previous->isNotEmpty())
            <section style="margin-top:var(--space-8, 32px)">
                <h2>{{ __('committee::committee.previous_terms') }}</h2>
                <ul>
                    @foreach ($previous as $term)
                        <li><a href="{{ url($base.'/committees/'.$term->slug()) }}">{{ $term->termLabel() ?? $term->name }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-theme::layout>
