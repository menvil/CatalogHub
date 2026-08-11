<?php

declare(strict_types=1);

namespace App\Support\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class RequestId
{
    public const ATTRIBUTE = 'cataloghub_request_id';

    public static function resolve(Request $request): string
    {
        $assigned = $request->attributes->get(self::ATTRIBUTE);

        if (is_string($assigned) && self::isValid($assigned)) {
            return $assigned;
        }

        $incoming = $request->header('X-Request-ID');
        $requestId = is_string($incoming) && self::isValid($incoming)
            ? $incoming
            : (string) Str::uuid();

        $request->attributes->set(self::ATTRIBUTE, $requestId);

        return $requestId;
    }

    public static function isValid(string $value): bool
    {
        return preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:-]{0,127}\z/', $value) === 1;
    }

    private function __construct() {}
}
