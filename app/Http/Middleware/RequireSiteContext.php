<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\Auth\SiteAdminAccess;
use App\Models\Site;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequireSiteContext
{
    public function __construct(private SiteAdminAccess $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $requestedSiteId = $this->requestedSiteId($request);
        $site = $this->access->resolveSite($user, $requestedSiteId);

        abort_unless($site instanceof Site, 403);

        $request->attributes->set('site_context', $site);

        return $next($request);
    }

    private function requestedSiteId(Request $request): ?int
    {
        $value = $request->query('site_id');

        if ($value === null) {
            return null;
        }

        abort_unless(is_string($value) && ctype_digit($value) && (int) $value > 0, 403);

        return (int) $value;
    }
}
