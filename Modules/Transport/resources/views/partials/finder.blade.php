{{-- Stop search (FR-TRN-06): type or pick a stop, see the routes that pass it. Contract: $stops (all stop names), $routes (list of name, group, stops), $link (true: links to /transport) --}}
<div class="transport-finder" x-data="{ q: '', routes: @js($routes),
        get hits() { const q = this.q.trim().toLowerCase(); if (q.length < 2) return []; return this.routes.filter(r => r.stops.some(s => s.toLowerCase().includes(q))) } }">
    <label style="display:block;font-weight:600" for="transport-stop-{{ $id ?? 'page' }}">{{ __('transport::transport.finder.label') }}</label>
    <input id="transport-stop-{{ $id ?? 'page' }}" type="search" x-model="q" list="transport-stops-{{ $id ?? 'page' }}" autocomplete="off"
           placeholder="{{ __('transport::transport.finder.placeholder') }}" style="width:100%;max-width:520px;margin-top:6px;padding:10px 12px;border:1px solid var(--color-border);border-radius:var(--radius-sm, 4px)">
    <datalist id="transport-stops-{{ $id ?? 'page' }}">
        @foreach ($stops as $stop)<option value="{{ $stop }}"></option>@endforeach
    </datalist>
    <div aria-live="polite" style="margin-top:10px">
        <template x-if="q.trim().length >= 2 && hits.length === 0"><p>{{ __('transport::transport.finder.none') }}</p></template>
        <ul x-show="hits.length" style="list-style:none;padding:0;margin:0;display:grid;gap:6px">
            <template x-for="r in hits" :key="r.group + r.id">
                <li>
                    @if ($link ?? false)
                        <a :href="'{{ url('/'.\Modules\Transport\Support\TransportWebsite::PATH) }}?service=' + r.group + '#route-' + r.id" x-text="r.name"></a>
                    @else
                        <a :href="'#route-' + r.id" x-on:click="$dispatch('transport-show', r.group)" x-text="r.name"></a>
                    @endif
                    <small style="color:var(--color-muted, #6b7280)" x-text="' · ' + r.service"></small>
                </li>
            </template>
        </ul>
    </div>
</div>
