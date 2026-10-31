{{-- /support-contacts (FR-OFF-07): a card per person to contact, searched in place. Contract: $page (SupportDirectory), $contacts (with office) --}}
<x-theme::layout :title="$page->title()">
    <div class="container" style="padding-block:var(--space-8, 32px);max-width:960px" x-data="{ q: @js($page->filters()['q']) }">
        <h1>{{ $page->title() }}</h1>
        @if ($page->subtitle())<p style="color:var(--color-text-muted)">{{ $page->subtitle() }}</p>@endif
        <input type="search" x-model="q" placeholder="{{ __('office::office.directory.search') }}" aria-label="{{ __('office::office.directory.search') }}"
               style="width:100%;padding:var(--space-3, 12px);border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px);margin-block:var(--space-4, 16px)">
        <div style="display:grid;gap:var(--space-4, 16px)">
            @foreach ($page->cards() as $card)
                <article class="card" x-show="q.trim() === '' || $el.dataset.search.includes(q.trim().toLowerCase())" data-search="{{ $card['search'] }}"
                         style="padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius-md, 8px)">
                    <h3 style="margin:0">{{ $card['name'] }}</h3>
                    @if ($card['designation'])<p style="margin:.25rem 0">{{ $card['designation'] }}</p>@endif
                    <p style="margin:.25rem 0;color:var(--color-text-muted)">{{ $card['office'] }}@if ($card['location']) · {{ $card['location'] }}@endif</p>
                    <p style="margin:0">
                        @if ($card['phone'])<a href="tel:{{ $card['tel'] }}">{{ $card['phone'] }}</a>@endif
                        @if ($card['extension']) ({{ __('office::office.ext') }} {{ $card['extension'] }})@endif
                        @foreach ($card['emails'] as $email) · <a href="mailto:{{ $email }}">{{ $email }}</a>@endforeach
                    </p>
                    <p style="margin:.25rem 0 0"><strong>{{ $card['purpose'] }}</strong></p>
                </article>
            @endforeach
        </div>
    </div>
</x-theme::layout>
