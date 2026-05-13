<?php

declare(strict_types=1);

namespace App\Repository;

use App\Config\Database;
use App\Model\FileRecord;
use PDO;

class FileRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?FileRecord
    {
        $stmt = $this->db->prepare('
            SELECT id, original_name, stored_name, mime_type, size, created_at
            FROM files
            WHERE id = :id
        ');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        return $data ? FileRecord::fromArray($data) : null;
    }

    public function findByStoredName(string $storedName): ?FileRecord
    {
        $stmt = $this->db->prepare('
            SELECT id, original_name, stored_name, mime_type, size, created_at
            FROM files
            WHERE stored_name = :stored_name
        ');
        $stmt->execute(['stored_name' => $storedName]);
        $data = $stmt->fetch();

        return $data ? FileRecord::fromArray($data) : null;
    }

    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('
            SELECT id, original_name, stored_name, mime_type, size, created_at
            FROM files
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $records = [];
        while ($data = $stmt->fetch()) {
            $records[] = FileRecord::fromArray($data);
        }

        return $records;
    }

    public function save(FileRecord $fileRecord): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO files (original_name, stored_name, mime_type, size, created_at)
            VALUES (:original_name, :stored_name, :mime_type, :size, :created_at)
        ');
        $stmt->execute([
            'original_name' => $fileRecord->getOriginalName(),
            'stored_name' => $fileRecord->getStoredName(),
            'mime_type' => $fileRecord->getMimeType(),
            'size' => $fileRecord->getSize(),
            'created_at' => $fileRecord->getCreatedAt(),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM files WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function update(FileRecord $fileRecord): bool
    {
        if ($fileRecord->getId() === null) {
            throw new \InvalidArgumentException('Cannot update file record without an ID');
        }

        $stmt = $this->db->prepare('
            UPDATE files
            SET original_name = :original_name, stored_name = :stored_name,
                mime_type = :mime_type, size = :size
            WHERE id = :id
        ');
        return $stmt->execute([
            'id' => $fileRecord->getId(),
            'original_name' => $fileRecord->getOriginalName(),
            'stored_name' => $fileRecord->getStoredName(),
            'mime_type' => $fileRecord->getMimeType(),
            'size' => $fileRecord->getSize(),
        ]);
    }

    public function count(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM files');
        return (int) $stmt->fetchColumn();
    }
}
