{{-- Homepage section "Find your bus" (FR-TRN-06). Contract: $items (timetable, stops), $section, $settings --}}
@if ($items['timetable']['ok'] ?? false)
    @php($routes = collect($items['timetable']['groups'])->flatMap(fn ($list, $g) => collect($list)->map(fn ($r) => ['id' => $r['id'], 'name' => $r['name'], 'group' => $g, 'service' => __("transport::transport.groups.{$g}"), 'stops' => $r['stops']]))->values())
    @include('transport::partials.finder', ['stops' => $items['stops'], 'routes' => $routes, 'link' => true, 'id' => 'home'])
    <p style="margin-top:var(--space-4, 16px)"><a class="button" href="{{ url('/'.\Modules\Transport\Support\TransportWebsite::PATH) }}">{{ __('transport::transport.all_routes') }}</a></p>
@endif
