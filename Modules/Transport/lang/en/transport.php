<?php

return [
    'title' => 'Transport',
    'description' => 'Bus routes, stops and times for :semester.',
    'running' => 'Now running',
    'policy' => 'Transport rules (PDF)',
    'unavailable' => 'The transport schedule cannot be shown right now. Please try again a little later.',
    'stale' => 'The transport system did not answer just now — this is the schedule as of :time.',
    'day_off' => 'No bus on',
    'to_campus' => 'To campus',
    'from_campus' => 'From campus',
    'shuttle' => 'shuttle',
    'stops' => '{1} :count stop|[2,*] :count stops',
    'notes' => 'Notes',
    'map' => 'Map',
    'all_routes' => 'All routes and times',
    'groups' => [
        'regular_road' => 'Regular',
        'shuttle_road' => 'Shuttle',
        'friday_road' => 'Friday',
    ],
    'finder' => [
        'label' => 'Find your bus',
        'placeholder' => 'Type your stop, e.g. Mirpur 10',
        'none' => 'No route passes a stop with that name.',
    ],
    'section' => [
        'label' => 'Find your bus (Transport)',
    ],
    'settings' => [
        'api_url' => 'Transport API address',
        'api_url_help' => 'The live transport system\'s public API (GET, JSON). Routes and times are kept there.',
        'cache_minutes' => 'Keep the answer for (minutes)',
        'timeout' => 'Wait for the API at most',
    ],
];
