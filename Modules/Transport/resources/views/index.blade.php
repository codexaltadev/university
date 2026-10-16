{{--
    /transport (FR-TRN-06/07): from the live transport system's API. Contract: $timetable (ok, stale, fetched_at, title, semester,
    policy_pdf, service, groups: regular_road / shuttle_road / friday_road → routes), $group (the open tab), $stops (all stop names)
--}}
@php($routes = collect($timetable['groups'])->flatMap(fn ($list, $g) => collect($list)->map(fn ($r) => ['id' => $r['id'], 'name' => $r['name'], 'group' => $g, 'service' => __("transport::transport.groups.{$g}"), 'stops' => $r['stops']]))->values())
@php($ampm = fn (string $t) => \Illuminate\Support\Carbon::createFromFormat('H:i', $t)->format('g:i A'))
<x-theme::layout :title="__('transport::transport.title')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('transport::transport.title') }}</h1>

        @if (! $timetable['ok'])
            <p role="status" style="margin-top:var(--space-6, 24px)">{{ __('transport::transport.unavailable') }}</p>
        @else
            <div style="display:flex;flex-wrap:wrap;gap:var(--space-3, 12px) var(--space-6, 24px);align-items:baseline;margin-top:var(--space-2, 8px)">
                @if ($timetable['title'])<p style="margin:0;font-weight:600">{{ $timetable['title'] }}</p>@endif
                @if ($timetable['semester'])<p style="margin:0">{{ $timetable['semester'] }}</p>@endif
                @if ($timetable['service'])<p style="margin:0">{{ __('transport::transport.running') }}: <strong>{{ $timetable['service'] }}</strong></p>@endif
                @if ($timetable['policy_pdf'])<p style="margin:0"><a href="{{ $timetable['policy_pdf'] }}" target="_blank" rel="noopener">{{ __('transport::transport.policy') }}</a></p>@endif
            </div>
            @if ($timetable['stale'])
                <p role="status" style="margin-top:var(--space-3, 12px);padding:8px 12px;border-radius:var(--radius-sm, 4px);background:color-mix(in srgb, var(--color-accent, #39B54A) 15%, transparent)">
                    {{ __('transport::transport.stale', ['time' => \Illuminate\Support\Carbon::parse($timetable['fetched_at'])->diffForHumans()]) }}
                </p>
            @endif

            <section style="margin-top:var(--space-6, 24px);padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius, 8px)">
                @include('transport::partials.finder', ['stops' => $stops, 'routes' => $routes, 'link' => false])
            </section>

            <div x-data="{ tab: @js($group) }" x-on:transport-show.window="tab = $event.detail" style="margin-top:var(--space-8, 32px)">
                <div role="tablist" aria-label="{{ __('transport::transport.title') }}" style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px)">
                    @foreach ($timetable['groups'] as $key => $list)
                        <button type="button" role="tab" x-on:click="tab = @js($key)" x-bind:aria-selected="tab === @js($key)" x-bind:class="tab === @js($key) ? 'button' : 'button button--outline'"
                                class="{{ $key === $group ? 'button' : 'button button--outline' }}">{{ __("transport::transport.groups.{$key}") }} ({{ count($list) }})</button>
                    @endforeach
                </div>

                @foreach ($timetable['groups'] as $key => $list)
                    <div role="tabpanel" x-show="tab === @js($key)" @if ($key !== $group) style="display:none" @endif>
                    <div style="display:grid;gap:var(--space-5, 20px);margin-top:var(--space-5, 20px);grid-template-columns:repeat(auto-fill,minmax(320px,1fr))">
                        @foreach ($list as $route)
                            <article id="route-{{ $route['id'] }}" style="border:1px solid var(--color-border);border-radius:var(--radius, 8px);padding:var(--space-4, 16px);scroll-margin-top:90px" x-data="{ map: false, all: false }">
                                <h2 style="font-size:1.15rem;margin:0">{{ $route['name'] }}</h2>
                                @if ($route['from'] || $route['to'])<p style="margin:4px 0 0;color:var(--color-muted, #6b7280)">{{ $route['from'] }} → {{ $route['to'] }}</p>@endif
                                @if ($route['days_off'])<p style="margin:4px 0 0"><small>{{ __('transport::transport.day_off') }}: {{ implode(', ', $route['days_off']) }}</small></p>@endif

                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3, 12px);margin-top:var(--space-3, 12px)">
                                    @foreach (['to_campus', 'from_campus'] as $direction)
                                        <div>
                                            <strong style="font-size:.9em">{{ __("transport::transport.{$direction}") }}</strong>
                                            <ul style="list-style:none;padding:0;margin:4px 0 0;display:flex;flex-wrap:wrap;gap:6px">
                                                @forelse ($route[$direction] as $trip)
                                                    <li style="padding:2px 8px;border-radius:999px;background:var(--color-surface, #F3F4F9);font-variant-numeric:tabular-nums">{{ $ampm($trip['time']) }}@if ($trip['type'] === 'shuttle')<small> · {{ __('transport::transport.shuttle') }}</small>@endif</li>
                                                @empty
                                                    <li><small>—</small></li>
                                                @endforelse
                                            </ul>
                                        </div>
                                    @endforeach
                                </div>

                                @if ($route['stops'])
                                    <details style="margin-top:var(--space-3, 12px)">
                                        <summary>{{ trans_choice('transport::transport.stops', count($route['stops']), ['count' => count($route['stops'])]) }}</summary>
                                        <ol style="margin:6px 0 0;padding-inline-start:1.3em">@foreach ($route['stops'] as $stop)<li>{{ $stop }}</li>@endforeach</ol>
                                    </details>
                                @endif
                                @if ($route['notes'])
                                    <details style="margin-top:var(--space-2, 8px)">
                                        <summary>{{ __('transport::transport.notes') }}</summary>
                                        <ol style="margin:6px 0 0;padding-inline-start:1.3em">@foreach ($route['notes'] as $note)<li>{{ $note }}</li>@endforeach</ol>
                                    </details>
                                @endif
                                @if ($route['map'])
                                    <p style="margin-top:var(--space-3, 12px)"><button type="button" class="button button--outline" x-on:click="map = ! map" x-bind:aria-expanded="map">{{ __('transport::transport.map') }}</button></p>
                                    <template x-if="map">
                                        <iframe src="{{ $route['map'] }}" title="{{ __('transport::transport.map') }} — {{ $route['name'] }}" loading="lazy" style="width:100%;height:320px;border:0;border-radius:var(--radius-sm, 4px)"></iframe>
                                    </template>
                                @endif
                            </article>
                        @endforeach
                    </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-theme::layout>
