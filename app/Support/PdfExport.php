<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Minimal HTML→PDF via browser print CSS, or plain HTML download labelled as report.
 * For shared hosts without Composer PDF libs.
 */
final class PdfExport
{
    public static function htmlDocument(string $title, string $bodyHtml): string
    {
        return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>'
            . htmlspecialchars($title)
            . '</title><style>
            body{font-family:Poppins,Arial,sans-serif;color:#101915;margin:32px}
            h1{color:#0B3D2E} table{width:100%;border-collapse:collapse;margin-top:16px}
            th,td{border:1px solid #ddd;padding:8px;text-align:left;font-size:13px}
            .muted{color:#5B6B63}
            @media print{body{margin:12mm}}
            </style></head><body>'
            . $bodyHtml
            . '<script>window.onload=function(){window.print()}</script></body></html>';
    }

    public static function stream(string $title, string $bodyHtml): void
    {
        header('Content-Type: text/html; charset=utf-8');
        echo self::htmlDocument($title, $bodyHtml);
    }
}
