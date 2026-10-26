{{--
    Service unavailable (503, `php artisan down` during an update): the same page as the website's maintenance (Settings ›
    Maintenance, drawn by the theme when it has one), else a plain page — the database may be away, so nothing here may need it.
--}}
@php
    try {
        $maintenance = [
            'message' => settings('maintenance.message') ?: __('frontend.maintenance'),
            'siteName' => settings('general.site_name') ?: config('app.name'),
            'duration' => settings('maintenance.duration') ?: null,
            'email' => settings('maintenance.help_email') ?: null,
        ];
    } catch (\Throwable) {
        $maintenance = ['message' => __('frontend.maintenance'), 'siteName' => config('app.name'), 'duration' => null, 'email' => null];
    }
@endphp
@if (view()->exists('settings::maintenance'))
    @include('settings::maintenance', $maintenance)
@else
    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $maintenance['siteName'] }}</title></head>
    <body style="margin:0;min-height:100vh;display:grid;place-items:center;font-family:system-ui,sans-serif;text-align:center">
        <main><h1>{{ $maintenance['siteName'] }}</h1><p>{{ $maintenance['message'] }}</p></main>
    </body>
    </html>
@endif
