{{-- One circular in a list, with its next date (countdown). Contract: $circular --}}
<article class="admission-circular card" style="padding:var(--space-6, 24px);border:1px solid var(--color-border);border-radius:var(--radius-md, 8px);background:var(--color-surface);{{ $circular->isClosed() ? 'opacity:.75' : '' }}">
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:var(--space-3, 12px);align-items:baseline">
        <h3 style="margin:0"><a href="{{ $circular->url() }}">{{ $circular->title }}</a></h3>
        <span class="badge {{ $circular->isClosed() ? 'badge--muted' : 'badge--success' }}">{{ __($circular->isClosed() ? 'admission::admission.closed' : 'admission::admission.open') }}</span>
    </div>
    @if (! $circular->isClosed() && ($next = $circular->nextDate()))
        @php($days = max(0, (int) today()->diffInDays($next['date'], false)))
        <p class="admission-countdown" style="margin:var(--space-2, 8px) 0 0">
            {{ __("admission::admission.dates.{$next['key']}") }}: <strong>{{ $next['date']->translatedFormat('d F Y') }}</strong>
            · {{ $days === 0 ? __('admission::admission.today') : trans_choice('admission::admission.days_left', $days, ['days' => $days]) }}
        </p>
    @endif
    @include('admission::partials.dates', ['circular' => $circular])
</article>
