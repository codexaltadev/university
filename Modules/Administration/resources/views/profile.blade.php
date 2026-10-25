{{--
    /administration/{slug} (FR-ADMN-05). Contract: $member, $person (PersonPresenter: public contacts only), $office (or null),
    $places (list of [place, url, role] — current public assignments), $categories, $counts (the sections, as on the directory)
--}}
<x-theme::layout :title="$person->name()">
    <div class="container" style="padding-block:var(--space-8, 32px)">
        <nav class="breadcrumb" aria-label="{{ __('platform::relations.breadcrumb') }}">
            <ol>
                <li><a href="{{ url('/') }}">{{ __('platform::relations.home') }}</a><span aria-hidden="true"> › </span></li>
                <li><a href="{{ url('/'.\Modules\Administration\Models\Member::PATH) }}">{{ __('administration::administration.plural') }}</a><span aria-hidden="true"> › </span></li>
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
                @if ($office)
                    <p>@if ($url = $office->url())<a href="{{ $url }}">{{ $office->name }}</a>@else{{ $office->name }}@endif</p>
                @endif
                <ul style="list-style:none;padding:0">
                    @if ($phone = $person->phone())<li>{{ __('administration::administration.fields.phone') }}: <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a>@if ($person->extension()) ({{ __('administration::administration.ext') }} {{ $person->extension() }})@endif</li>@endif
                    @if ($email = $person->email())<li>{{ __('administration::administration.fields.email') }}: <a href="mailto:{{ $email }}">{{ $email }}</a></li>@endif
                </ul>
            </div>
        </div>

        @if (filled($member->biography))
            <section style="margin-top:var(--space-8, 32px)">
                <h2>{{ __('administration::administration.fields.biography') }}</h2>
                <div class="prose">{!! \Illuminate\Support\Str::sanitizeHtml((string) $member->biography) !!}</div>
            </section>
        @endif

        @if ($places->isNotEmpty())
            <section style="margin-top:var(--space-8, 32px)">
                <h2>{{ __('administration::administration.sections.places') }}</h2>
                <ul>
                    @foreach ($places as $place)
                        <li>@if ($place['url'])<a href="{{ $place['url'] }}">{{ $place['place'] }}</a>@else{{ $place['place'] }}@endif — {{ $place['role'] }}</li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-theme::layout>
