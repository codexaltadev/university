{{-- "Get admission updates" block (FR-ADM-09). Contract: $data (heading, topic), $locale --}}
<div class="block block--admission-subscribe">
    @if (filled($data['heading'] ?? null))
        <h3>{{ $data['heading'] }}</h3>
    @endif
    <livewire:admission.subscribe :topic="(string) ($data['topic'] ?? 'admission') ?: 'admission'" />
</div>
