<?php

declare(strict_types=1);

namespace App\Service;

class FileValidator
{
    private array $allowedMimeTypes;
    private array $allowedExtensions;
    private int $maxFileSize;

    public function __construct(array $allowedMimeTypes = null, array $allowedExtensions = null, int $maxFileSize = null)
    {
        $this->allowedMimeTypes = $allowedMimeTypes ?? [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'application/pdf',
            'text/plain',
            'text/csv',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/x-tar',
            'application/gzip',
        ];

        $this->allowedExtensions = $allowedExtensions ?? [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
            'svg',
            'pdf',
            'txt',
            'csv',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'zip',
            'rar',
            '7z',
            'tar',
            'gz',
        ];

        $this->maxFileSize = $maxFileSize ?? 50 * 1024 * 1024; // 50MB default
    }

    public function validate(string $filename, string $mimeType, int $size): array
    {
        $errors = [];

        // Validate file size
        if ($size > $this->maxFileSize) {
            $errors[] = sprintf(
                'File size exceeds maximum limit of %d bytes',
                $this->maxFileSize
            );
        }

        // Validate MIME type
        if (!in_array($mimeType, $this->allowedMimeTypes, true)) {
            $errors[] = sprintf(
                'MIME type "%s" is not allowed. Allowed types: %s',
                $mimeType,
                implode(', ', $this->allowedMimeTypes)
            );
        }

        // Validate extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (empty($extension)) {
            $errors[] = 'File has no extension';
        } elseif (!in_array($extension, $this->allowedExtensions, true)) {
            $errors[] = sprintf(
                'Extension ".%s" is not allowed. Allowed extensions: %s',
                $extension,
                implode(', ', $this->allowedExtensions)
            );
        }

        // Validate filename doesn't contain path traversal
        if ($this->hasPathTraversal($filename)) {
            $errors[] = 'Filename contains invalid characters (path traversal attempt)';
        }

        return $errors;
    }

    private function hasPathTraversal(string $filename): bool
    {
        return str_contains($filename, '..') ||
            str_contains($filename, '/') ||
            str_contains($filename, '\\') ||
            str_contains($filename, "\0");
    }

    public function sanitizeFilename(string $filename): string
    {
        // Convert accents and special characters to ASCII equivalents
        // Using transliterator for better support of Latin characters
        if (function_exists('transliterator_transliterate')) {
            // First decompose accented characters
            $filename = transliterator_transliterate('NFKD', $filename);
            // Then remove combining marks (accents)
            $filename = preg_replace('/[\p{Mn}]/u', '', $filename);
        } else {
            // Fallback: manual mapping of common accented characters
            $replacements = [
                'ç' => 'c',
                'Ç' => 'c',
                'á' => 'a',
                'Á' => 'a',
                'à' => 'a',
                'À' => 'a',
                'ã' => 'a',
                'Ã' => 'a',
                'â' => 'a',
                'Â' => 'a',
                'é' => 'e',
                'É' => 'e',
                'è' => 'e',
                'È' => 'e',
                'ê' => 'e',
                'Ê' => 'e',
                'í' => 'i',
                'Í' => 'i',
                'ì' => 'i',
                'Ì' => 'i',
                'î' => 'i',
                'Î' => 'i',
                'ó' => 'o',
                'Ó' => 'o',
                'ò' => 'o',
                'Ò' => 'o',
                'õ' => 'o',
                'Õ' => 'o',
                'ô' => 'o',
                'Ô' => 'o',
                'ú' => 'u',
                'Ú' => 'u',
                'ù' => 'u',
                'Ù' => 'u',
                'û' => 'u',
                'Û' => 'u',
            ];
            $filename = strtr($filename, $replacements);
        }

        // Convert to lowercase
        $filename = strtolower($filename);

        // Remove path traversal and dangerous characters first (before generic replacement)
        $filename = str_replace(['..', '/', '\\', '|', "\0", "\t", "\n", "\r"], '', $filename);

        // Remove control characters
        $filename = preg_replace('/[\x00-\x1F\x7F]/', '', $filename);

        // Replace spaces with hyphens
        $filename = str_replace(' ', '-', $filename);

        // Replace remaining special characters (keep only alphanumeric, dots, hyphens, underscores)
        $filename = preg_replace('/[^a-z0-9._-]/', '', $filename);

        // Clean up multiple consecutive hyphens/underscores
        $filename = preg_replace('/[-_]{2,}/', '-', $filename);

        // Trim hyphens and underscores from edges
        $filename = trim($filename, '-_');

        return $filename;
    }

    public function generateSanitizedFilenameWithId(string $filename, int $id): string
    {
        // Remove path separators that might exist in the filename
        // This handles cases where the filename contains path elements from Windows or Unix
        $filename = str_replace(['/', '\\'], '', $filename);

        // Now safely extract the extension
        $pathInfo = pathinfo($filename);
        $basename = $pathInfo['filename'] ?? 'file';
        $extension = $pathInfo['extension'] ?? '';

        // Sanitize the basename
        $sanitizedBasename = $this->sanitizeFilename($basename);

        // Combine with ID and extension
        $finalName = $sanitizedBasename . '-' . $id;
        if (!empty($extension)) {
            $finalName .= '.' . strtolower($extension);
        }

        return $finalName;
    }
}
