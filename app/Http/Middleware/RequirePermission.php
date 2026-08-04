<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Models\Site;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequirePermission
{
    public function __construct(private AuthorizationService $authorization) {}

    public function handle(Request $request, Closure $next, string $permissionName): Response
    {
        $permission = Permission::tryFrom($permissionName)
            ?? throw new InvalidArgumentException("Unknown CatalogHub permission [{$permissionName}].");
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $site = $request->attributes->get('site_context');
        $this->authorization->authorizePage(
            $user,
            $permission,
            $site instanceof Site ? $site : null,
        );

        return $next($request);
    }
}
