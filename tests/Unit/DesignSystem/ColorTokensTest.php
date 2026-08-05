<?php

declare(strict_types=1);

namespace Tests\Unit\DesignSystem;

use PHPUnit\Framework\TestCase;

final class ColorTokensTest extends TestCase
{
    private const float MINIMUM_NORMAL_TEXT_CONTRAST = 4.5;

    public function test_text_and_status_pairs_meet_normal_text_contrast(): void
    {
        $root = dirname(__DIR__, 3);
        $palettePath = $root.'/resources/css/tokens/palette.css';
        $semanticPath = $root.'/resources/css/tokens/colors.css';
        $this->assertFileExists($palettePath);
        $this->assertFileExists($semanticPath);
        $paletteSource = file_get_contents($palettePath);
        $semanticSource = file_get_contents($semanticPath);
        $this->assertIsString($paletteSource);
        $this->assertIsString($semanticSource);
        $palette = $this->palette($paletteSource);
        $semantic = $this->semanticColors($semanticSource);
        $pairs = [
            ['--color-foundation-text', '--color-foundation-surface'],
            ['--color-foundation-text-muted', '--color-foundation-surface'],
            ['--color-foundation-text-muted', '--color-foundation-surface-muted'],
            ['--color-foundation-accent', '--color-foundation-surface'],
            ['--color-foundation-accent', '--color-foundation-accent-surface'],
            ['--color-foundation-success', '--color-foundation-success-surface'],
            ['--color-foundation-warning', '--color-foundation-warning-surface'],
            ['--color-foundation-danger', '--color-foundation-danger-surface'],
            ['--color-foundation-info', '--color-foundation-info-surface'],
        ];

        foreach ($pairs as [$foreground, $background]) {
            $ratio = $this->contrastRatio($palette[$semantic[$foreground]], $palette[$semantic[$background]]);

            $this->assertGreaterThanOrEqual(self::MINIMUM_NORMAL_TEXT_CONTRAST, $ratio, "Contrast pair [{$foreground}] / [{$background}] is {$ratio}:1.");
        }
    }

    public function test_alpha_palette_values_are_not_treated_as_opaque_colors(): void
    {
        $palette = $this->palette(<<<'CSS'
            @theme {
                --palette-opaque-short: #abc;
                --palette-alpha-short: #abcd;
                --palette-opaque-long: #aabbcc;
                --palette-alpha-long: #aabbccdd;
            }
            CSS);

        $this->assertSame([
            '--palette-opaque-short' => '#abc',
            '--palette-opaque-long' => '#aabbcc',
        ], $palette);
    }

    /** @return array<string, string> */
    private function palette(string $css): array
    {
        preg_match_all('/(--palette-[\w-]+):\s*(#(?:[0-9a-f]{6}|[0-9a-f]{3}))\b/i', $css, $matches, PREG_SET_ORDER);

        return array_column($matches, 2, 1);
    }

    /** @return array<string, string> */
    private function semanticColors(string $css): array
    {
        preg_match_all('/(--color-foundation-[\w-]+):\s*var\((--palette-[\w-]+)\)/', $css, $matches, PREG_SET_ORDER);

        return array_column($matches, 2, 1);
    }

    private function contrastRatio(string $foreground, string $background): float
    {
        $lighter = max($this->luminance($foreground), $this->luminance($background));
        $darker = min($this->luminance($foreground), $this->luminance($background));

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = implode('', array_map(static fn (string $channel): string => $channel.$channel, str_split($hex)));
        }

        $channels = array_map(
            static function (string $channel): float {
                $value = hexdec($channel) / 255;

                return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
            },
            str_split($hex, 2),
        );

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
