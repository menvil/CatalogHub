<?php

namespace App\Domains\Themes\Services;

use App\Models\BlockDefinition;
use Illuminate\Validation\ValidationException;

final class BlockConfigValidator
{
    /** @param array<string, mixed> $config */
    public function validate(BlockDefinition $block, array $config): void
    {
        $schema = $block->config_schema_json ?? [];
        $unknown = array_diff(array_keys($config), array_keys($schema));

        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'config' => 'Unknown block config key: '.reset($unknown),
            ]);
        }

        foreach ($config as $key => $value) {
            $definition = $schema[$key] ?? null;
            $type = is_array($definition) ? ($definition['type'] ?? null) : $definition;

            if (! is_string($type) || $type === 'nullable' || $value === null) {
                continue;
            }

            $valid = match ($type) {
                'string' => is_string($value),
                'integer' => is_int($value),
                'boolean' => is_bool($value),
                'array' => is_array($value),
                default => is_string($value) && in_array($value, explode('|', $type), true),
            };

            if (! $valid) {
                throw ValidationException::withMessages([
                    "config.{$key}" => "Block config {$key} must match {$type}.",
                ]);
            }
        }
    }
}
