<?php
declare(strict_types=1);

namespace App\Support;

final class Response
{
    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $path, int $status = 302): void
    {
        $base = rtrim(Env::get('APP_URL', '') ?: '', '/');
        $basePath = rtrim(Env::get('APP_BASE_PATH', '') ?: '', '/');
        if (str_starts_with($path, 'http')) {
            header('Location: ' . $path, true, $status);
            exit;
        }
        header('Location: ' . $base . $basePath . $path, true, $status);
        exit;
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /** @return list<array{type: string, message: string}> */
    public static function pullFlash(): array
    {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return is_array($flash) ? $flash : [];
    }
}
