<?php
declare(strict_types=1);

namespace App\Support;

/** Shared upload validation for deal rooms and surveys. */
final class UploadGuard
{
    private const ALLOWED = [
        'pdf' => ['application/pdf'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'txt' => ['text/plain'],
        'csv' => ['text/csv', 'text/plain', 'application/vnd.ms-excel'],
    ];

    /** @return array{ok:bool,error?:string,ext?:string,safe_name?:string} */
    public static function validate(array $file, int $maxBytes = 5242880): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload failed.'];
        }
        if (($file['size'] ?? 0) > $maxBytes) {
            return ['ok' => false, 'error' => 'File exceeds size limit (' . (int) ($maxBytes / 1048576) . 'MB).'];
        }
        $orig = (string) ($file['name'] ?? 'file.bin');
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED[$ext])) {
            return ['ok' => false, 'error' => 'File type not allowed.'];
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file((string) $file['tmp_name']) ?: '';
        $allowedMimes = self::ALLOWED[$ext];
        if ($mime && !in_array($mime, $allowedMimes, true) && !str_starts_with($mime, 'text/')) {
            // allow slight mime drift for office docs on Windows
            if (!in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv'], true)) {
                return ['ok' => false, 'error' => 'MIME type rejected.'];
            }
        }
        $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $orig) ?: ('file.' . $ext);
        return ['ok' => true, 'ext' => $ext, 'safe_name' => $safe];
    }
}
