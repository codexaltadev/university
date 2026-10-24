{{-- Homepage section "Distinguished Visitors" (FR-INTL-06): photo, name, title, country, their words; a link to all. Contract: $items (Visitor list), $section, $settings --}}
@if ($items->isNotEmpty())
    <div style="display:grid;gap:var(--space-4, 16px);grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
        @foreach ($items as $visitor)
            <figure style="margin:0;padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius, 8px)">
                <blockquote style="margin:0 0 var(--space-3, 12px)">{{ \Illuminate\Support\Str::limit(trim(strip_tags((string) $visitor->body)), 220) }}</blockquote>
                <figcaption><strong>{{ $visitor->name }}</strong><br><small>{{ collect([$visitor->designation, $visitor->organization])->filter()->implode(', ') }}@if ($visitor->country) · {{ $visitor->country->name }}@endif</small></figcaption>
            </figure>
        @endforeach
    </div>
    <p style="margin-top:var(--space-4, 16px)"><a class="btn btn--outline" href="{{ url('/'.\Modules\International\Models\Visitor::PATH) }}">{{ __('international::international.see_all') }}</a></p>
@endif
