<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\AppConfig;
use App\Model\FileRecord;
use App\Repository\FileRepository;

class FileStorageService
{
    private FileRepository $repository;
    private string $storagePath;
    private FileValidator $fileValidator;

    public function __construct()
    {
        $this->repository = new FileRepository();
        $this->storagePath = AppConfig::getInstance()->getStoragePath();
        $this->fileValidator = new FileValidator();
        $this->ensureStoragePathExists();
    }

    public function storeFile(string $originalName, string $tempPath, string $mimeType, int $size): FileRecord
    {
        // First, store with a temporary name
        $tempStoredName = $this->generateStoredName($originalName);
        $tempDestination = $this->storagePath . '/' . $tempStoredName;

        if (!move_uploaded_file($tempPath, $tempDestination)) {
            if (!rename($tempPath, $tempDestination)) {
                throw new \RuntimeException('Failed to store file');
            }
        }

        // Save file record to get the ID
        $fileRecord = new FileRecord(
            null,
            $originalName,
            $tempStoredName,
            $mimeType,
            $size
        );

        $id = $this->repository->save($fileRecord);
        $fileRecord->setId($id);

        // Generate final name with sanitization and ID
        $finalStoredName = $this->fileValidator->generateSanitizedFilenameWithId($originalName, $id);
        $finalDestination = $this->storagePath . '/' . $finalStoredName;

        // Rename the file to final name
        if (!rename($tempDestination, $finalDestination)) {
            // If rename fails, try to delete the temporary file and fail gracefully
            if (file_exists($tempDestination)) {
                unlink($tempDestination);
            }
            throw new \RuntimeException('Failed to rename file to final name');
        }

        // Update the file record with the final name
        $fileRecord->setStoredName($finalStoredName);
        $this->repository->update($fileRecord);

        return $fileRecord;
    }

    public function getFileById(int $id): ?FileRecord
    {
        return $this->repository->findById($id);
    }

    public function getFileByStoredName(string $storedName): ?FileRecord
    {
        return $this->repository->findByStoredName($storedName);
    }

    public function getFilePath(FileRecord $fileRecord): string
    {
        $storedName = $fileRecord->getStoredName();

        // Security: validate that stored name doesn't contain path traversal
        if (str_contains($storedName, '/') || str_contains($storedName, '\\') || str_contains($storedName, '..')) {
            throw new \RuntimeException('Invalid stored file name detected');
        }

        $fullPath = $this->storagePath . '/' . $storedName;

        // Ensure the real path is within storage directory
        $realPath = realpath($fullPath);
        $realStoragePath = realpath($this->storagePath);

        if ($realPath === false || !str_starts_with($realPath, $realStoragePath)) {
            throw new \RuntimeException('File path is outside storage directory');
        }

        return $fullPath;
    }

    public function getAllFiles(int $limit = 100, int $offset = 0): array
    {
        $records = $this->repository->findAll($limit, $offset);
        return array_map(fn($record) => $record->toArray(), $records);
    }

    public function deleteFile(int $id): bool
    {
        $fileRecord = $this->repository->findById($id);
        if ($fileRecord === null) {
            return false;
        }

        $filePath = $this->getFilePath($fileRecord);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        return $this->repository->delete($id);
    }

    private function generateStoredName(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $uniqueId = bin2hex(random_bytes(16));
        return $uniqueId . ($extension ? '.' . $extension : '');
    }

    private function ensureStoragePathExists(): void
    {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }
}
