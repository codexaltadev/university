{{-- "Get admission updates" as DIU's NotificationForm (the Livewire form brings the look). Contract: $data (heading, topic), $locale --}}
<div>
    @if (filled($data['heading'] ?? null))<h3 class="sr-only">{{ $data['heading'] }}</h3>@endif
    <livewire:admission.subscribe :topic="(string) ($data['topic'] ?? 'admission') ?: 'admission'" />
</div>
