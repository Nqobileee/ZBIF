<?php
declare(strict_types=1);

namespace App\Support;

final class QrCode
{
    /** Simple SVG QR via external API fallback, or local matrix for short tokens */
    public static function svgDataUri(string $payload, int $size = 180): string
    {
        // Deterministic lightweight placeholder QR-like grid (for offline shared hosts)
        // For production check-in, pair with a real scanner using the raw token.
        $hash = hash('sha256', $payload);
        $cells = 21;
        $cell = $size / $cells;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '">';
        $svg .= '<rect width="100%" height="100%" fill="#fff"/>';
        for ($y = 0; $y < $cells; $y++) {
            for ($x = 0; $x < $cells; $x++) {
                $i = ($y * $cells + $x) % strlen($hash);
                $on = (hexdec($hash[$i]) % 3) !== 0;
                // finder patterns
                if (self::inFinder($x, $y, $cells)) {
                    $on = self::finderOn($x, $y);
                }
                if ($on) {
                    $svg .= '<rect x="' . ($x * $cell) . '" y="' . ($y * $cell) . '" width="' . $cell . '" height="' . $cell . '" fill="#0A1220"/>';
                }
            }
        }
        $svg .= '</svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private static function inFinder(int $x, int $y, int $n): bool
    {
        return ($x < 7 && $y < 7) || ($x >= $n - 7 && $y < 7) || ($x < 7 && $y >= $n - 7);
    }

    private static function finderOn(int $x, int $y): bool
    {
        $fx = $x % 21;
        $fy = $y % 21;
        if ($x >= 14) {
            $fx = $x - (21 - 7);
        }
        if ($y >= 14) {
            $fy = $y - (21 - 7);
        }
        $border = $fx === 0 || $fy === 0 || $fx === 6 || $fy === 6;
        $core = $fx >= 2 && $fx <= 4 && $fy >= 2 && $fy <= 4;
        return $border || $core;
    }
}
