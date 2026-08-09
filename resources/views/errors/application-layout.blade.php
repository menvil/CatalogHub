@php($requestId ??= null)
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>@yield('title') — {{ config('app.name', 'CatalogHub') }}</title>
        <style>
            :root { color-scheme: light; font-family: ui-sans-serif, system-ui, sans-serif; }
            * { box-sizing: border-box; }
            body { margin: 0; background: #f7f8fb; color: #172033; }
            main { display: grid; min-height: 100vh; place-items: center; padding: 2rem 1rem; }
            section { width: min(100%, 40rem); border: 1px solid #d8deea; border-radius: .75rem; background: #fff; padding: 2rem; }
            .status { color: #667085; font-size: .75rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; }
            h1 { margin: .75rem 0 0; font-size: clamp(1.75rem, 5vw, 2.5rem); }
            p { color: #4b5565; line-height: 1.6; }
            code { word-break: break-all; }
        </style>
    </head>
    <body data-application-error="@yield('status')">
        <main>
            <section aria-labelledby="application-error-title">
                <p class="status">@yield('status')</p>
                <h1 id="application-error-title">@yield('heading')</h1>
                <p>@yield('message')</p>
                @if ($requestId !== null)
                    <p>Request ID: <code>{{ $requestId }}</code></p>
                @endif
            </section>
        </main>
    </body>
</html>
