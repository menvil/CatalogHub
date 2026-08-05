<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Support\PublicSite\PublicErrorContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class PublicErrorResponse
{
    public function render(Response $response, Throwable $exception, Request $request): Response
    {
        $status = $response->getStatusCode();
        $routeName = $request->route()?->getName();
        $context = $request->attributes->get(PublicErrorContext::class);
        $isPublic = $context instanceof PublicErrorContext
            || (is_string($routeName) && str_starts_with($routeName, 'public.'));

        if (! $isPublic || $request->expectsJson() || ! in_array($status, [404, 500, 503], true)) {
            return $response;
        }

        $requestId = $status === 500 ? $this->requestId($request) : null;
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

    private function requestId(Request $request): string
    {
        $requestId = $request->header('X-Request-ID');

        return is_string($requestId) && preg_match('/\A[A-Za-z0-9._:-]{1,128}\z/', $requestId) === 1
            ? $requestId
            : (string) Str::uuid();
    }
}
