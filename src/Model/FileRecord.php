<?php

declare(strict_types=1);

namespace App\Model;

class FileRecord
{
    private ?int $id;
    private string $originalName;
    private string $storedName;
    private string $mimeType;
    private int $size;
    private string $createdAt;

    public function __construct(
        ?int $id,
        string $originalName,
        string $storedName,
        string $mimeType,
        int $size,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->originalName = $originalName;
        $this->storedName = $storedName;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['original_name'],
            $data['stored_name'],
            $data['mime_type'],
            (int) $data['size'],
            $data['created_at'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->originalName,
            'stored_name' => $this->storedName,
            'mime_type' => $this->mimeType,
            'size' => $this->size,
            'created_at' => $this->createdAt,
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getStoredName(): string
    {
        return $this->storedName;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
