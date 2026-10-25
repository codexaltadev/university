{{-- /admission/contact (DIU's /admission-contact): the Admission Office and its contact groups. Contract: $page (Support\AdmissionPage) --}}
<x-theme::layout :title="__('admission::admission.contact_page.title')">
    <div class="container" style="padding-block:var(--space-8, 32px)">
        <h1>{{ __('admission::admission.contact_page.title') }}</h1>
        @php($office = $page->office())
        @if ($office['name'])<h2>{{ $office['name'] }}</h2>@endif
        @if ($office['address'])<p>{{ $office['address'] }}</p>@endif
        @if ($office['helpline'])<p>{{ __('admission::admission.contact_page.helpline') }}: <a href="tel:{{ $office['tel'] }}">{{ $office['helpline'] }}</a></p>@endif
        @if ($office['email'])<p>{{ __('admission::admission.contact_page.email') }}: <a href="mailto:{{ $office['email'] }}">{{ $office['email'] }}</a></p>@endif
        @foreach ($page->contacts() as $group)
            <h2>{{ $group['name'] }}</h2>
            <ul>
                @foreach ($group['people'] as $person)
                    <li><strong>{{ $person['name'] }}</strong>@if ($person['designation']) — {{ $person['designation'] }}@endif
                        @if ($person['phone']) · <a href="tel:{{ $person['tel'] }}">{{ $person['phone'] }}</a>@endif
                        @if ($person['email']) · <a href="mailto:{{ $person['email'] }}">{{ $person['email'] }}</a>@endif</li>
                @endforeach
            </ul>
        @endforeach
    </div>
</x-theme::layout>
