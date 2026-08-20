<?php
declare(strict_types=1);

namespace App\Support;

final class Str
{
    public static function slug(string $text): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $text) ?: 'item');
        return trim($slug, '-') ?: 'item';
    }

    public static function noEmDash(string $text): string
    {
        return str_replace(["\u{2014}", "\u{2013}", '—', '–'], [',', ',', ',', ','], $text);
    }
}
