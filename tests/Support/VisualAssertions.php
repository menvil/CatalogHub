<?php

declare(strict_types=1);

namespace Tests\Support;

use GdImage;
use InvalidArgumentException;
use RuntimeException;

final class VisualAssertions
{
    public static function compare(
        string $baseline,
        string $current,
        string $artifactRoot,
        string $name,
        float $maximumMeanDifference,
    ): float {
        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $name) !== 1) {
            throw new InvalidArgumentException('Visual artifact names must use lower-case kebab-case.');
        }

        $baselineImage = self::decode($baseline);
        $currentImage = self::decode($current);

        if (imagesx($baselineImage) !== imagesx($currentImage)
            || imagesy($baselineImage) !== imagesy($currentImage)) {
            throw new RuntimeException('Visual baseline and current image dimensions differ.');
        }

        $currentDirectory = $artifactRoot.'/current';
        $diffDirectory = $artifactRoot.'/diff';
        self::ensureDirectory($currentDirectory);
        self::ensureDirectory($diffDirectory);

        if (! copy($current, $currentDirectory.'/'.$name.'.png')) {
            throw new RuntimeException('Unable to preserve the current visual capture.');
        }

        $difference = 0.0;
        $samples = 0;
        $diff = imagecreatetruecolor(imagesx($baselineImage), imagesy($baselineImage));

        if (! $diff instanceof GdImage) {
            throw new RuntimeException('Unable to create a visual diff image.');
        }

        for ($y = 0; $y < imagesy($baselineImage); $y++) {
            for ($x = 0; $x < imagesx($baselineImage); $x++) {
                $baselineRgb = self::rgbAt($baselineImage, $x, $y);
                $currentRgb = self::rgbAt($currentImage, $x, $y);
                $pixelDifference = 0;

                foreach ([0, 1, 2] as $channel) {
                    $channelDifference = abs($baselineRgb[$channel] - $currentRgb[$channel]);
                    $difference += $channelDifference / 255;
                    $pixelDifference = max($pixelDifference, $channelDifference);
                    $samples++;
                }

                $color = imagecolorallocate($diff, $pixelDifference, 0, 0);
                imagesetpixel($diff, $x, $y, $color);
            }
        }

        $meanDifference = $difference / $samples;

        if ($meanDifference > $maximumMeanDifference) {
            imagepng($diff, $diffDirectory.'/'.$name.'.png');
            throw new RuntimeException(sprintf(
                'Visual mismatch for [%s]: %.6f exceeds %.6f.',
                $name,
                $meanDifference,
                $maximumMeanDifference,
            ));
        }

        return $meanDifference;
    }

    private static function decode(string $path): GdImage
    {
        $image = @imagecreatefrompng($path);

        if (! $image instanceof GdImage) {
            throw new RuntimeException("Unable to decode visual image [{$path}].");
        }

        return $image;
    }

    /** @return array{int, int, int} */
    private static function rgbAt(GdImage $image, int $x, int $y): array
    {
        $color = imagecolorat($image, $x, $y);

        if (! imageistruecolor($image)) {
            $palette = imagecolorsforindex($image, $color);

            return [$palette['red'], $palette['green'], $palette['blue']];
        }

        return [($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF];
    }

    private static function ensureDirectory(string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create visual artifact directory [{$directory}].");
        }
    }
}
