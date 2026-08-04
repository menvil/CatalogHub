<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\Auth\CentralAdminAccess;
use App\Contracts\Auth\LegacySiteAdminRouteAccess;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureCentralAdminAccess
{
    public function __construct(
        private CentralAdminAccess $access,
        private LegacySiteAdminRouteAccess $legacySiteAccess,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User
                && ($this->access->allows($user)
                    || $this->legacySiteAccess->allows($user, $request->route()?->getName())),
            403,
        );

        return $next($request);
    }
}
