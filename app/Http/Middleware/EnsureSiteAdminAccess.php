<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\Auth\SiteAdminAccess;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureSiteAdminAccess
{
    public function __construct(private SiteAdminAccess $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && $this->access->allows($user), 403);

        return $next($request);
    }
}
