<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class ApplicationErrorResponse
{
    public function __construct(private PublicErrorResponse $publicErrors) {}

    public function render(Response $response, Throwable $exception, Request $request): Response
    {
        $publicResponse = $this->publicErrors->render($response, $exception, $request);

        if ($publicResponse !== $response) {
            return $publicResponse;
        }

        $status = $response->getStatusCode();

        if (! $request->expectsJson() && $status === 403 && $this->adminContext($request) !== null) {
            return $this->adminResponse($response, $request);
        }

        return $response;
    }

    private function adminResponse(Response $response, Request $request): Response
    {
        $context = $this->adminContext($request);
        $isCentral = $context === 'central-admin';
        $rendered = response()->view('errors.admin.403', [
            'presentationContext' => $context,
            'dashboardUrl' => $isCentral ? '/admin/central' : '/admin/site',
            'dashboardLabel' => $isCentral ? 'Return to Central Admin' : 'Return to Site Admin',
            'requestId' => null,
        ], 403);

        return $this->copyHeaders($response, $rendered);
    }

    private function adminContext(Request $request): ?string
    {
        return match (true) {
            $request->is('admin/central', 'admin/central/*') => 'central-admin',
            $request->is('admin/site', 'admin/site/*') => 'site-admin',
            default => null,
        };
    }

    private function copyHeaders(Response $source, Response $target): Response
    {
        foreach ($source->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            if (strtolower($name) !== 'content-length') {
                $target->headers->set($name, $values);
            }
        }

        return $target;
    }
}
