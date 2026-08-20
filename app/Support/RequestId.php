<?php
declare(strict_types=1);

namespace App\Support;

final class RequestId
{
    private static ?string $id = null;

    public static function ensure(): string
    {
        if (self::$id === null) {
            self::$id = bin2hex(random_bytes(8));
            header('X-Request-Id: ' . self::$id);
        }
        return self::$id;
    }

    public static function get(): string
    {
        return self::ensure();
    }
}
