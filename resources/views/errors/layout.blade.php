<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | {{ config('app.name') }}</title>
    <style>
        :root { --bg: #f5f6fa; --fg: #121a2b; --muted: #586178; --accent: #465fff; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #0d1220; --fg: #e6e9f3; --muted: #96a0b8; --accent: #8fa2ff; }
        }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; box-sizing: border-box; padding: 16px;
               background: var(--bg); color: var(--fg); font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { max-width: 32rem; }
        .code { margin: 0; font-size: .9rem; letter-spacing: .08em; color: var(--muted); }
        h1 { margin: .25rem 0 .75rem; font-size: 1.75rem; }
        p { line-height: 1.5; }
        a { color: var(--accent); }
    </style>
</head>
<body>
    <main>
        <p class="code">Error @yield('code')</p>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <p><a href="{{ url('/') }}">Back to the bid board</a></p>
    </main>
</body>
</html>
