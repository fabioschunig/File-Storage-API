<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\FileStorageService;
use App\Service\FileValidator;
use App\Config\AppConfig;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Stream;

class FileController
{
    private FileStorageService $fileService;
    private FileValidator $fileValidator;

    public function __construct()
    {
        $this->fileService = new FileStorageService();
        $config = AppConfig::getInstance();
        $this->fileValidator = new FileValidator(
            null,
            null,
            $config->getMaxFileSize()
        );
    }

    public function upload(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $uploadedFiles = $request->getUploadedFiles();
        $parsedBody = $request->getParsedBody();

        if (empty($uploadedFiles['file'])) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'No file provided'
            ], 400);
        }

        $file = $uploadedFiles['file'];

        if ($file->getError() !== UPLOAD_ERR_OK) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Upload error: ' . $this->getUploadErrorMessage($file->getError())
            ], 400);
        }

        // Validate file
        $filename = $file->getClientFilename() ?? 'unnamed';
        $mimeType = $file->getClientMediaType() ?? 'application/octet-stream';
        $size = $file->getSize();

        $validationErrors = $this->fileValidator->validate($filename, $mimeType, $size);
        if (!empty($validationErrors)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'File validation failed',
                'details' => $validationErrors
            ], 400);
        }

        try {
            $fileRecord = $this->fileService->storeFile(
                $filename,
                $file->getFilePath(),
                $mimeType,
                $size
            );

            $metadata = $parsedBody['metadata'] ?? null;

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'id' => $fileRecord->getId(),
                    'original_name' => $fileRecord->getOriginalName(),
                    'mime_type' => $fileRecord->getMimeType(),
                    'size' => $fileRecord->getSize(),
                    'created_at' => $fileRecord->getCreatedAt(),
                    'metadata' => $metadata,
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Failed to store file: ' . $e->getMessage()
            ], 500);
        }
    }

    public function download(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) ($args['id'] ?? 0);
        $fileRecord = $this->fileService->getFileById($id);

        if ($fileRecord === null) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'File not found'
            ], 404);
        }

        $filePath = $this->fileService->getFilePath($fileRecord);

        if (!file_exists($filePath)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'File not found on storage'
            ], 404);
        }

        $sanitizedFilename = $this->fileValidator->sanitizeFilename($fileRecord->getOriginalName());
        $dispositionHeader = $this->buildContentDisposition($sanitizedFilename);
        
        $response = $response
            ->withHeader('Content-Type', $fileRecord->getMimeType())
            ->withHeader('Content-Length', (string) $fileRecord->getSize())
            ->withHeader('Content-Disposition', $dispositionHeader);

        // Stream file to avoid loading entire file into memory
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Cannot read file'
            ], 500);
        }

        $stream = new \Slim\Psr7\Stream($handle);
        return $response->withBody($stream);
    }

    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $limit = min((int) ($queryParams['limit'] ?? 100), 1000);
        $offset = (int) ($queryParams['offset'] ?? 0);

        $files = $this->fileService->getAllFiles($limit, $offset);

        return $this->jsonResponse($response, [
            'success' => true,
            'data' => $files,
            'meta' => [
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($files),
            ]
        ]);
    }

    private function jsonResponse(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }

    private function buildContentDisposition(string $filename): string
    {
        // RFC 5987 encoding: filename*=UTF-8''encoded-filename
        // Also provide fallback filename for older browsers
        $utf8Filename = urlencode($filename);
        // Replace certain characters according to RFC 5987
        $utf8Filename = str_replace('%', '%25', $utf8Filename);
        
        return sprintf(
            'attachment; filename="%s"; filename*=UTF-8\'\' %s',
            $filename,
            $utf8Filename
        );
    }

    private function getUploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
            default => 'Unknown error',
        };
    }
}
