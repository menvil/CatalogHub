<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>@yield('title') — {{ $presentationContext === 'central-admin' ? 'CatalogHub Central' : 'CatalogHub Site Admin' }}</title>
        <style>
            :root { color-scheme: light; font-family: ui-sans-serif, system-ui, sans-serif; }
            * { box-sizing: border-box; }
            body { margin: 0; background: #f5f7fb; color: #172033; }
            main { display: grid; min-height: 100vh; place-items: center; padding: 2rem 1rem; }
            section { width: min(100%, 40rem); border: 1px solid #d8deea; border-radius: .75rem; background: #fff; padding: 2rem; box-shadow: 0 1rem 3rem rgb(23 32 51 / 8%); }
            .context, .status { color: #667085; font-size: .75rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; }
            h1 { margin: .75rem 0 0; font-size: clamp(1.75rem, 5vw, 2.5rem); line-height: 1.15; }
            p { color: #4b5565; line-height: 1.6; }
            a { display: inline-flex; margin-top: 1rem; border-radius: .5rem; background: #243b64; color: #fff; font-weight: 600; padding: .7rem 1rem; text-decoration: none; }
            code { word-break: break-all; }
        </style>
    </head>
    <body data-presentation-context="{{ $presentationContext }}" data-admin-error="@yield('status')">
        <main>
            <section aria-labelledby="admin-error-title">
                <p class="context">{{ $presentationContext === 'central-admin' ? 'Central administration' : 'Site administration' }}</p>
                <p class="status">@yield('status')</p>
                <h1 id="admin-error-title">@yield('heading')</h1>
                <p>@yield('message')</p>
                @if ($requestId !== null)
                    <p>Request ID: <code>{{ $requestId }}</code></p>
                @endif
                <a href="{{ $dashboardUrl }}">{{ $dashboardLabel }}</a>
            </section>
        </main>
    </body>
</html>
