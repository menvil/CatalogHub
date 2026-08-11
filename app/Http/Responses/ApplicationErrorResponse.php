<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Exceptions\Domain\DomainException;
use App\Support\Http\RequestId;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class ApplicationErrorResponse
{
    public function __construct(private PublicErrorResponse $publicErrors) {}

    public function render(Response $response, Throwable $exception, Request $request): Response
    {
        $requestId = RequestId::resolve($request);

        if ($exception instanceof DomainException) {
            return $this->domainResponse($response, $exception, $request, $requestId);
        }

        $publicResponse = $this->publicErrors->render($response, $exception, $request);

        if ($publicResponse !== $response) {
            $publicResponse->headers->set('X-Request-ID', $requestId);

            return $publicResponse;
        }

        $status = $response->getStatusCode();

        if (! $request->expectsJson() && in_array($status, [403, 404, 500, 503], true) && $this->adminContext($request) !== null) {
            return $this->adminResponse($response, $request, $status, $requestId);
        }

        if (! $request->expectsJson() && in_array($status, [500, 503], true)) {
            return $this->applicationResponse($response, $status, $requestId);
        }

        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    private function domainResponse(Response $response, DomainException $exception, Request $request, string $requestId): Response
    {
        $response->setStatusCode($exception->status());
        $response->setContent('');

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'message' => $exception->publicMessage(),
                'request_id' => $requestId,
            ], $exception->status(), ['X-Request-ID' => $requestId]);
        }

        $publicResponse = $this->publicErrors->render($response, $exception, $request);

        if ($publicResponse !== $response) {
            $publicResponse->headers->set('X-Request-ID', $requestId);

            return $publicResponse;
        }

        if (in_array($exception->status(), [403, 404], true) && $this->adminContext($request) !== null) {
            return $this->adminResponse($response, $request, $exception->status(), $requestId);
        }

        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    private function adminResponse(Response $response, Request $request, int $status, string $requestId): Response
    {
        $context = $this->adminContext($request);
        $isCentral = $context === 'central-admin';
        $rendered = response()->view("errors.admin.{$status}", [
            'presentationContext' => $context,
            'dashboardUrl' => $isCentral ? '/admin/central' : '/admin/site',
            'dashboardLabel' => $isCentral ? 'Return to Central Admin' : 'Return to Site Admin',
            'requestId' => $status === 500 ? $requestId : null,
        ], $status);

        return $this->copyHeaders($response, $rendered, $requestId);
    }

    private function applicationResponse(Response $response, int $status, string $requestId): Response
    {
        $rendered = response()->view("errors.{$status}", [
            'requestId' => $status === 500 ? $requestId : null,
        ], $status);

        return $this->copyHeaders($response, $rendered, $requestId);
    }

    private function adminContext(Request $request): ?string
    {
        return match (true) {
            $request->is('admin/central', 'admin/central/*') => 'central-admin',
            $request->is('admin/site', 'admin/site/*') => 'site-admin',
            default => null,
        };
    }

    private function copyHeaders(Response $source, Response $target, string $requestId): Response
    {
        foreach ($source->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            if (strtolower($name) !== 'content-length') {
                $target->headers->set($name, $values);
            }
        }

        $target->headers->set('X-Request-ID', $requestId);

        return $target;
    }
}
