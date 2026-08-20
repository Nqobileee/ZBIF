<?php
declare(strict_types=1);

namespace App\Support;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/public'): string
    {
        $viewFile = ZBIF_ROOT . '/views/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException("View not found: {$view}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        if ($layout === null) {
            return $content;
        }

        $layoutFile = ZBIF_ROOT . '/views/' . str_replace('.', '/', $layout) . '.php';
        if (!is_file($layoutFile)) {
            throw new \RuntimeException("Layout not found: {$layout}");
        }
        ob_start();
        require $layoutFile;
        return (string) ob_get_clean();
    }

    /** @param array<string, mixed> $data */
    public static function make(string $view, array $data = [], ?string $layout = 'layouts/public'): void
    {
        echo self::render($view, $data, $layout);
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
