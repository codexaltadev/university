{{-- /support-contacts (FR-OFF-07): who to contact, searched by purpose. Contract: $contacts (with office) --}}
<x-theme::layout :title="__('office::office.support.plural')">
    <div class="container" style="padding-block:var(--space-8, 32px);max-width:960px" x-data="{ q: '' }">
        <h1>{{ __('office::office.support.plural') }}</h1>
        <input type="search" x-model="q" placeholder="{{ __('office::office.search_support') }}" aria-label="{{ __('office::office.search_support') }}"
               style="width:100%;padding:var(--space-3, 12px);border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px);margin-block:var(--space-4, 16px)">
        <div style="display:grid;gap:var(--space-4, 16px)">
            @foreach ($contacts as $contact)
                <article class="card" x-show="q.trim() === '' || $el.dataset.search.includes(q.trim().toLowerCase())"
                         data-search="{{ mb_strtolower($contact->purpose.' '.$contact->office->name) }}"
                         style="padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius-md, 8px)">
                    <h3 style="margin:0">{{ $contact->purpose }}</h3>
                    <p style="margin:.25rem 0;color:var(--color-text-muted)">{{ $contact->office->name }}@if (filled($contact->location)) · {{ $contact->location }}@endif</p>
                    <ul style="list-style:none;padding:0;margin:0;display:grid;gap:.25rem">
                        @foreach ($contact->people(\Modules\Office\Models\OfficeSupportContact::ROLE) as $person)
                            <li><strong>{{ $person->name() }}</strong>@if ($person->designation()), {{ $person->designation() }}@endif
                                @if ($person->phone()) · <a href="tel:{{ preg_replace('/[^0-9+]/', '', $person->phone()) }}">{{ $person->phone() }}</a>@endif
                                @if ($person->extension()) ({{ __('office::office.ext') }} {{ $person->extension() }})@endif
                                @if ($person->email()) · <a href="mailto:{{ $person->email() }}">{{ $person->email() }}</a>@endif</li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>
    </div>
</x-theme::layout>
