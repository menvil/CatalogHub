<?php

namespace App\Support\Database;

final class LiteralLikePattern
{
    public const string ESCAPE_CHARACTER = '!';

    public static function containing(string $value): string
    {
        return '%'.str_replace(
            [self::ESCAPE_CHARACTER, '%', '_'],
            [self::ESCAPE_CHARACTER.self::ESCAPE_CHARACTER, self::ESCAPE_CHARACTER.'%', self::ESCAPE_CHARACTER.'_'],
            $value,
        ).'%';
    }
}
