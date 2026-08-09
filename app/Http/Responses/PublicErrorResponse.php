<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Support\Http\RequestId;
use App\Support\PublicSite\PublicErrorContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class PublicErrorResponse
{
    public function render(Response $response, Throwable $exception, Request $request): Response
    {
        $status = $response->getStatusCode();
        $routeName = $request->route()?->getName();
        $context = $request->attributes->get(PublicErrorContext::class);
        $isPublic = is_string($routeName) && str_starts_with($routeName, 'public.');

        if (! $isPublic || $request->expectsJson() || ! in_array($status, [404, 500, 503], true)) {
            return $response;
        }

        $requestId = $status === 500 ? RequestId::resolve($request) : null;
        $rendered = response()->view("errors.public.{$status}", [
            'errorContext' => $context instanceof PublicErrorContext ? $context : null,
            'requestId' => $requestId,
        ], $status);

        foreach ($response->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            if (strtolower($name) !== 'content-length') {
                $rendered->headers->set($name, $values);
            }
        }

        return $rendered;
    }
}
