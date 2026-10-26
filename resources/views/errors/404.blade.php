{{--
    Page not found (404): the active theme's errors/404 with the website around it (as the main site when no page set the
    context), else a plain page. If the theme cannot draw (a broken setting …), Laravel's own page answers.
--}}
@php(\Illuminate\Support\Facades\View::share('context', \Illuminate\Support\Facades\View::shared('context') ?? \Platform\Context\SiteContext::site()))
@if (view()->exists('theme::errors.404'))
    @include('theme::errors.404')
@else
    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>404</title></head>
    <body style="margin:0;min-height:100vh;display:grid;place-items:center;font-family:system-ui,sans-serif;text-align:center">
        <main><h1>404</h1><p>{{ __('frontend.not_found') }}</p><p><a href="{{ url('/') }}">{{ url('/') }}</a></p></main>
    </body>
    </html>
@endif
