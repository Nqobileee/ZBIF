<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Pure-PHP PDF writer (PDF 1.4) for shared hosts without Composer PDF libs.
 */
final class SimplePdf
{
    /** @var list<list<array{t:string,s:int}>> */
    private array $pages = [];
    /** @var list<array{t:string,s:int}> */
    private array $current = [];
    private float $y = 800.0;
    private string $title;

    public function __construct(string $title = 'ZBIF Report')
    {
        $this->title = $title;
    }

    public function heading(string $text): void
    {
        $this->ensureSpace(36);
        $this->current[] = ['t' => $text, 's' => 18];
        $this->y -= 28;
    }

    public function text(string $text): void
    {
        foreach ($this->wrap($text, 90) as $line) {
            $this->ensureSpace(16);
            $this->current[] = ['t' => $line, 's' => 11];
            $this->y -= 14;
        }
    }

    public function spacer(int $pts = 12): void
    {
        $this->y -= $pts;
        if ($this->y < 72) {
            $this->newPage();
        }
    }

    public function footerBrand(): void
    {
        $this->spacer(20);
        $this->text('ZBIF InnovaMatch | ZB Financial Holdings | Confidential stakeholder report');
    }

    public function stream(string $filename): void
    {
        if ($this->current) {
            $this->pages[] = $this->current;
            $this->current = [];
        }
        if (!$this->pages) {
            $this->pages[] = [['t' => 'Empty report', 's' => 12]];
        }
        $bin = $this->build();
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($bin));
        echo $bin;
        exit;
    }

    private function newPage(): void
    {
        $this->pages[] = $this->current;
        $this->current = [];
        $this->y = 800.0;
    }

    private function ensureSpace(int $need): void
    {
        if ($this->y - $need < 72) {
            $this->newPage();
        }
    }

    /** @return list<string> */
    private function wrap(string $text, int $width): array
    {
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?: '';
        if ($text === '') {
            return [''];
        }
        $words = explode(' ', $text);
        $lines = [];
        $line = '';
        foreach ($words as $w) {
            $try = $line === '' ? $w : $line . ' ' . $w;
            if (strlen($try) > $width) {
                if ($line !== '') {
                    $lines[] = $line;
                }
                $line = $w;
            } else {
                $line = $try;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }
        return $lines ?: [''];
    }

    private function build(): string
    {
        $fontId = 3;
        $nextId = 4;
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[$fontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $kids = [];
        foreach ($this->pages as $pageLines) {
            $contentId = $nextId++;
            $pageId = $nextId++;
            $kids[] = $pageId . ' 0 R';

            $parts = ["BT", "/F1 11 Tf", "50 800 Td"];
            $first = true;
            foreach ($pageLines as $item) {
                $size = (int) $item['s'];
                $safe = $this->escape($item['t']);
                if (!$first) {
                    $parts[] = '0 -' . ($size + 4) . ' Td';
                }
                $parts[] = "/F1 {$size} Tf";
                $parts[] = "({$safe}) Tj";
                $first = false;
            }
            $parts[] = 'ET';
            $stream = implode("\n", $parts);
            $objects[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n{$stream}\nendstream";
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents '
                . $contentId . ' 0 R /Resources << /Font << /F1 ' . $fontId . ' 0 R >> >> >> >>';
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        ksort($objects);
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $max = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($max + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size " . ($max + 1) . " /Root 1 0 R /Info << /Title ("
            . $this->escape($this->title) . ") /Producer (ZBIF SimplePdf) >> >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";
        return $pdf;
    }

    private function escape(string $s): string
    {
        $s = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $s) ?: $s;
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    }
}
