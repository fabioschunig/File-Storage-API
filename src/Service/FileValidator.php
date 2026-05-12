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
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
            'pdf',
            'txt', 'csv',
            'doc', 'docx',
            'xls', 'xlsx',
            'zip', 'rar', '7z', 'tar', 'gz',
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
        // Remove path traversal characters
        $filename = str_replace(['..', '/', '\\', "\0"], '', $filename);
        
        // Remove control characters
        $filename = preg_replace('/[\x00-\x1F\x7F]/', '', $filename);
        
        // Remove null bytes
        $filename = str_replace("\0", '', $filename);
        
        // Trim whitespace
        $filename = trim($filename);
        
        return $filename;
    }
}
