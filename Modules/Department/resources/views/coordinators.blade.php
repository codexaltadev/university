{{-- /coordination-officers. Contract: $page (CoordinatorsPage: title, subtitle, groups — faculty, people: name, designation, unit, email, phone, tel, extension, photo) --}}
<x-theme::layout :title="$page->title()">
    <div class="container" style="padding-block:var(--space-8, 32px)">
        <h1>{{ $page->title() }}</h1>
        @if ($page->subtitle())<p>{{ $page->subtitle() }}</p>@endif
        @forelse ($page->groups() as $group)
            <section style="margin-block:var(--space-6, 24px)">
                <h2>{{ $group['faculty'] }}</h2>
                <ul style="list-style:none;padding:0;display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
                    @foreach ($group['people'] as $person)
                        <li class="card" style="padding:var(--space-4, 16px);border:1px solid var(--color-border)">
                            <strong>{{ $person['name'] }}</strong>@if ($person['unit']) ({{ $person['unit'] }})@endif
                            @if ($person['designation'])<br>{{ $person['designation'] }}@endif
                            @if ($person['email'])<br><a href="mailto:{{ $person['email'] }}">{{ $person['email'] }}</a>@endif
                            @if ($person['phone'])<br><a href="tel:{{ $person['tel'] }}">{{ $person['phone'] }}</a>@endif
                            @if ($person['extension'])<br>{{ __('department::department.coordinators.ext') }} {{ $person['extension'] }}@endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <p>{{ __('department::department.coordinators.none') }}</p>
        @endforelse
    </div>
</x-theme::layout>
