<?php

namespace App\Helpers;

use App\Exceptions\ValidationException;

class FileHelper
{
    private static array $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private static int $maxSize = 10485760; // 10MB

    public static function upload(array $file, string $folder): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException(['file' => ['File upload failed']]);
        }

        if (!in_array($file['type'], self::$allowedImageTypes)) {
            throw new ValidationException(['file' => ['Invalid file type. Only JPG, PNG, GIF, WEBP allowed']]);
        }

        if ($file['size'] > self::$maxSize) {
            throw new ValidationException(['file' => ['File size exceeds 10MB limit']]);
        }

        $uploadDir = UPLOAD_PATH . "/{$folder}/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename  = uniqid('', true) . '.' . strtolower($extension);
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \RuntimeException('Could not save uploaded file');
        }

        return "/uploads/{$folder}/{$filename}";
    }

    public static function delete(string $relativePath): void
    {
        $fullPath = PUBLIC_PATH . $relativePath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}
