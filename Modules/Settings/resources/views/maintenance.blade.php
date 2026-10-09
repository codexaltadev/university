<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $siteName }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: system-ui, sans-serif; background: #f8fafc; color: #1e293b; }
        main { max-width: 32rem; padding: 2rem; text-align: center; }
        h1 { font-size: 1.5rem; margin: 0 0 1rem; }
        p { line-height: 1.6; margin: 0; }
        @media (prefers-color-scheme: dark) { body { background: #0f172a; color: #e2e8f0; } }
    </style>
</head>
<body>
    <main>
        <h1>{{ $siteName }}</h1>
        <p>{{ $message }}</p>
    </main>
</body>
</html>
