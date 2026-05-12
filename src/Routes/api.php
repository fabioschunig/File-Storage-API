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
            'endpoints' => [
                'POST /api/files' => 'Upload a file',
                'GET /api/files' => 'List all files',
                'GET /api/files/{id}' => 'Download a file by ID'
            ],
            'authentication' => 'API Key required via X-API-Key header'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // API Routes (protected by API Key)
    $app->group('/api', function ($group) use ($fileController) {
        $group->post('/files', [$fileController, 'upload']);
        $group->get('/files', [$fileController, 'list']);
        $group->get('/files/{id}', [$fileController, 'download']);
    })->add($apiKeyMiddleware);
};
