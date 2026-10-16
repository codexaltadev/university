{{--
    /transport (FR-TRN-06/07) — in the order of DIU's current page: introduction, Transport Pass, the schedule (title, semester,
    rules), stop search, Regular / Shuttle / Friday routes, fees. Routes and times from the live transport system's API; the other
    text from Settings › Transport.
    Contract: $timetable (ok, stale, fetched_at, title, semester, policy_pdf, service, groups), $group (open tab), $stops (all stop
    names), $page (intro, pass_title, pass_subtitle, pass_how, pass_why, pass_image (MediaItem|null), fees (route, tri, bi), fees_note)
--}}
@php($routes = collect($timetable['groups'])->flatMap(fn ($list, $g) => collect($list)->map(fn ($r) => ['id' => $r['id'], 'name' => $r['name'], 'group' => $g, 'service' => __("transport::transport.groups.{$g}"), 'stops' => $r['stops']]))->values())
@php($ampm = fn (string $t) => \Illuminate\Support\Carbon::createFromFormat('H:i', $t)->format('g:i A'))
<x-theme::layout :title="__('transport::transport.title')">
    <div class="container" style="padding-block:var(--space-10, 40px)">
        <h1>{{ __('transport::transport.title') }}</h1>
        @if ($page['intro'])<p style="max-width:780px;font-size:1.1rem">{{ $page['intro'] }}</p>@endif

        {{-- Transport Pass --}}
        @if ($page['pass_title'])
            <section style="display:grid;gap:var(--space-6, 24px);align-items:center;margin-top:var(--space-8, 32px);grid-template-columns:repeat(auto-fit,minmax(280px,1fr))">
                @if ($page['pass_image'])
                    <img src="{{ $page['pass_image']->url('large') ?? $page['pass_image']->url() }}" alt="{{ $page['pass_image']->alt_text ?: $page['pass_title'] }}" loading="lazy" style="width:100%;max-width:360px;border-radius:var(--radius, 8px)">
                @endif
                <div>
                    <h2 style="margin:0">{{ $page['pass_title'] }}</h2>
                    @if ($page['pass_subtitle'])<p style="font-size:1.1rem;color:var(--color-muted, #6b7280)">{{ $page['pass_subtitle'] }}</p>@endif
                    @if ($page['pass_how'])<h3 style="margin-bottom:4px">{{ __('transport::transport.pass.how') }}</h3><p style="margin-top:0">{{ $page['pass_how'] }}</p>@endif
                    @if ($page['pass_why'])<h3 style="margin-bottom:4px">{{ __('transport::transport.pass.why') }}</h3><p style="margin-top:0">{{ $page['pass_why'] }}</p>@endif
                </div>
            </section>
        @endif

        @if (! $timetable['ok'])
            <p role="status" style="margin-top:var(--space-8, 32px)">{{ __('transport::transport.unavailable') }}</p>
        @else
            {{-- The schedule --}}
            <section style="display:flex;flex-wrap:wrap;gap:var(--space-4, 16px);justify-content:space-between;align-items:center;margin-top:var(--space-8, 32px);padding:var(--space-4, 16px) var(--space-5, 20px);border:1px solid var(--color-border);border-inline-start:4px solid var(--color-primary);border-radius:var(--radius, 8px)">
                <div>
                    <h2 style="margin:0;font-size:1.4rem">{{ __('transport::transport.schedule_title', ['service' => $timetable['service'] ?: __('transport::transport.groups.regular_road')]) }}</h2>
                    @if ($timetable['title'] && ! str_contains(strtolower($timetable['title']), 'regular'))<p style="margin:4px 0 0;font-weight:600">{{ $timetable['title'] }}</p>@endif
                    @if ($timetable['semester'])<p style="margin:4px 0 0;color:var(--color-muted, #6b7280)">{{ $timetable['semester'] }}</p>@endif
                </div>
                @if ($timetable['policy_pdf'])<a class="button" href="{{ $timetable['policy_pdf'] }}" target="_blank" rel="noopener">{{ __('transport::transport.policy') }}</a>@endif
            </section>
            @if ($timetable['stale'])
                <p role="status" style="margin-top:var(--space-3, 12px);padding:8px 12px;border-radius:var(--radius-sm, 4px);background:color-mix(in srgb, var(--color-accent, #39B54A) 15%, transparent)">
                    {{ __('transport::transport.stale', ['time' => \Illuminate\Support\Carbon::parse($timetable['fetched_at'])->diffForHumans()]) }}
                </p>
            @endif

            <section style="margin-top:var(--space-5, 20px);padding:var(--space-4, 16px);border:1px solid var(--color-border);border-radius:var(--radius, 8px)">
                @include('transport::partials.finder', ['stops' => $stops, 'routes' => $routes, 'link' => false])
            </section>

            <div x-data="{ tab: @js($group) }" x-on:transport-show.window="tab = $event.detail" style="margin-top:var(--space-6, 24px)">
                <div role="tablist" aria-label="{{ __('transport::transport.title') }}" style="display:flex;flex-wrap:wrap;gap:var(--space-2, 8px);border-bottom:1px solid var(--color-border)">
                    @foreach ($timetable['groups'] as $key => $list)
                        <button type="button" role="tab" x-on:click="tab = @js($key)" x-bind:aria-selected="tab === @js($key)"
                                x-bind:style="{ borderBottomColor: tab === @js($key) ? 'var(--color-primary)' : 'transparent', color: tab === @js($key) ? 'var(--color-primary)' : 'inherit' }"
                                style="background:none;border:0;border-bottom:3px solid transparent;padding:10px 16px;font:inherit;font-weight:600;cursor:pointer">{{ __("transport::transport.groups.{$key}") }} <small>({{ count($list) }})</small></button>
                    @endforeach
                </div>

                @foreach ($timetable['groups'] as $key => $list)
                    <div role="tabpanel" x-show="tab === @js($key)" @if ($key !== $group) style="display:none" @endif>
                    <div style="display:grid;gap:var(--space-5, 20px);margin-top:var(--space-5, 20px);grid-template-columns:repeat(auto-fill,minmax(320px,1fr))">
                        @foreach ($list as $route)
                            <article id="route-{{ $route['id'] }}" style="border:1px solid var(--color-border);border-radius:var(--radius, 8px);padding:var(--space-4, 16px);scroll-margin-top:90px" x-data="{ map: false }">
                                <h3 style="font-size:1.15rem;margin:0">{{ $route['name'] }}</h3>
                                @if ($route['days_off'])<p style="margin:4px 0 0"><small>{{ __('transport::transport.day_off') }}: {{ implode(', ', $route['days_off']) }}</small></p>@endif

                                @foreach (['to_campus' => $route['from'], 'from_campus' => $route['to']] as $direction => $place)
                                    <div style="margin-top:var(--space-3, 12px)">
                                        <strong style="font-size:.9em">{{ $place ? __('transport::transport.departure_from', ['place' => $place]) : __("transport::transport.{$direction}") }}</strong>
                                        <ul style="list-style:none;padding:0;margin:6px 0 0;display:flex;flex-wrap:wrap;gap:6px">
                                            @forelse ($route[$direction] as $trip)
                                                <li style="padding:3px 10px;border-radius:999px;background:var(--color-surface, #F3F4F9);font-variant-numeric:tabular-nums">{{ $ampm($trip['time']) }}@if ($trip['type'] === 'shuttle' && $key !== 'shuttle_road')<small> · {{ __('transport::transport.shuttle') }}</small>@endif</li>
                                            @empty
                                                <li><small>—</small></li>
                                            @endforelse
                                        </ul>
                                    </div>
                                @endforeach

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

        {{-- Fees (Settings › Transport) --}}
        @if ($page['fees'])
            <section style="margin-top:var(--space-10, 40px)">
                <h2>{{ __('transport::transport.fees.title') }}</h2>
                <div style="overflow-x:auto">
                    <table style="border-collapse:collapse;min-width:420px">
                        <thead><tr style="border-bottom:2px solid var(--color-border);text-align:start">
                            <th style="padding:10px 16px">{{ __('transport::transport.fees.route') }}</th>
                            <th style="padding:10px 16px">{{ __('transport::transport.fees.tri') }}</th>
                            <th style="padding:10px 16px">{{ __('transport::transport.fees.bi') }}</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($page['fees'] as $fee)
                                <tr style="border-bottom:1px solid var(--color-border)">
                                    <td style="padding:10px 16px">{{ $fee['route'] ?? '' }}</td>
                                    <td style="padding:10px 16px;font-weight:600;color:var(--color-primary)">{{ $fee['tri'] ?? '—' }}</td>
                                    <td style="padding:10px 16px;font-weight:600;color:var(--color-primary)">{{ $fee['bi'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($page['fees_note'])<p style="margin-top:var(--space-3, 12px)">{{ $page['fees_note'] }}</p>@endif
            </section>
        @endif
    </div>
</x-theme::layout>
