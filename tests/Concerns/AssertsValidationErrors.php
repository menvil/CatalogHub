<?php

namespace Tests\Concerns;

use Closure;
use Illuminate\Validation\ValidationException;

trait AssertsValidationErrors
{
    protected function assertValidationError(string $field, Closure $callback): void
    {
        try {
            $callback();
            $this->fail("Expected validation to fail for {$field}.");
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }
}
