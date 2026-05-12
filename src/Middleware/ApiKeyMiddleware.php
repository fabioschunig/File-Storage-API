<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config\AppConfig;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class ApiKeyMiddleware implements MiddlewareInterface
{
    private const HEADER_NAME = 'X-API-Key';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $apiKey = $this->extractApiKey($request);
        $expectedKey = AppConfig::getInstance()->getApiKey();

        if ($apiKey === null || !hash_equals($expectedKey, $apiKey)) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Invalid or missing API key'
            ]));

            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }

    private function extractApiKey(ServerRequestInterface $request): ?string
    {
        // Only accept API Key from header for security
        // (prevents exposure in logs, browser history, or request body logs)
        $header = $request->getHeaderLine(self::HEADER_NAME);
        if (!empty($header)) {
            return $header;
        }

        return null;
    }
}
