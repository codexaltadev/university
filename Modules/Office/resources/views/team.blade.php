{{-- /office/{slug}/team: the whole team, head first (FR-OFF-05). Contract: $entity (Office), $page (OfficePage) --}}
<x-theme::layout :title="__('office::office.page.team_members')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('office::office.page.team_members') }}</h1>
        <ul style="list-style:none;padding:0;display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
            @foreach ($page->team() as $person)
                <li class="card" style="padding:var(--space-4, 16px)">
                    @if ($person->photoUrl())<img src="{{ $person->photoUrl() }}" alt="" loading="lazy" style="width:96px;height:96px;border-radius:50%;object-fit:cover">@endif
                    <strong style="display:block">{{ $person->name() }}</strong>
                    <span style="color:var(--color-text-muted)">{{ $person->designation() }}</span>
                    @if ($person->phone())<div><a href="tel:{{ $person->phone() }}">{{ $person->phone() }}</a></div>@endif
                    @if ($person->email())<div><a href="mailto:{{ $person->email() }}">{{ $person->email() }}</a></div>@endif
                </li>
            @endforeach
        </ul>
    </div>
</x-theme::layout>
