<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Site;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireSiteContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $site = $user->site()->first();

        abort_unless($site instanceof Site, 403);

        $request->attributes->set('site_context', $site);

        return $next($request);
    }
}
