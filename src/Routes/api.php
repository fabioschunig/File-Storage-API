<?php

declare(strict_types=1);

use App\Controller\FileController;
use App\Middleware\ApiKeyMiddleware;
use Slim\App;

return function (App $app): void {
    $fileController = new FileController();
    $apiKeyMiddleware = new ApiKeyMiddleware();

    // Root route - API information
    $app->get('/', function ($request, $response) {
        $response->getBody()->write(json_encode([
            'name' => 'File Storage API',
            'version' => '1.0.0',
            'authentication' => 'API Key required only for upload and list operations'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Public route - handle /api/files without filename (returns error message)
    $handleFilesWithoutFilename = function ($request, $response) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Filename not provided',
            'message' => 'Please specify a filename. Usage: /api/files/{filename}'
        ], JSON_PRETTY_PRINT));
        return $response
            ->withStatus(400)
            ->withHeader('Content-Type', 'application/json');
    };

    $app->get('/api/files', $handleFilesWithoutFilename);
    $app->get('/api/files/', $handleFilesWithoutFilename);

    // Protected routes (require API Key) - Upload
    $app->group('/api', function ($group) use ($fileController) {
        $group->post('/files', [$fileController, 'upload']);
    })->add($apiKeyMiddleware);

    // Public route (no authentication required) - Download by filename
    $app->get('/api/files/{filename}', [$fileController, 'download']);
};
