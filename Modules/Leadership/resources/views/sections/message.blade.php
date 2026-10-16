{{-- Homepage section "Message from …" (FR-LEAD-06). Contract: $items (?LeadershipTerm — the current holder), $section, $settings (position_id, words) --}}
@if ($items)
    @php($person = $items->person())
    <div class="leadership-message-section" style="display:flex;flex-wrap:wrap;gap:var(--space-6, 24px);align-items:center">
        @if ($photo = $person->photoUrl())
            <img src="{{ $photo }}" alt="{{ $person->name() }}" loading="lazy" style="width:200px;height:200px;border-radius:50%;object-fit:cover">
        @endif
        <div style="flex:1;min-width:260px">
            <p class="eyebrow">{{ __('leadership::leadership.section.from', ['position' => $items->position->title]) }}</p>
            <blockquote style="margin:var(--space-3, 12px) 0">{{ \Illuminate\Support\Str::words(trim(strip_tags((string) $items->message)), (int) (($settings['words'] ?? null) ?: 80)) }}</blockquote>
            <p><strong>{{ $person->name() }}</strong><br>{{ $items->position->title }}</p>
            <p><a href="{{ url($items->url()) }}" class="button">{{ __('leadership::leadership.section.read_more') }}</a></p>
        </div>
    </div>
@endif
