<?php

declare(strict_types=1);

namespace App\Config;

use Dotenv\Dotenv;

class AppConfig
{
    private static ?self $instance = null;
    private array $config;

    private function __construct()
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->safeLoad();

        $this->config = [
            'db' => [
                'host' => $_ENV['DB_HOST'] ?? 'db',
                'name' => $_ENV['DB_NAME'] ?? 'file_storage_api',
                'user' => $_ENV['DB_USER'] ?? 'file_storage_api',
                'password' => $_ENV['DB_PASSWORD'] ?? 'file_storage_api',
            ],
            'storage' => [
                'path' => $_ENV['STORAGE_PATH'] ?? '/var/www/html/storage',
            ],
            'api' => [
                'key' => $_ENV['API_KEY'] ?? 'default-api-key-change-this',
            ],
            'upload' => [
                'max_file_size' => (int) ($_ENV['MAX_FILE_SIZE'] ?? 52428800), // 50MB default
            ],
            'app' => [
                'env' => $_ENV['APP_ENV'] ?? 'production',
                'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
            ],
        ];
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function getDbConfig(): array
    {
        return $this->config['db'];
    }

    public function getStoragePath(): string
    {
        return $this->config['storage']['path'];
    }

    public function getApiKey(): string
    {
        return $this->config['api']['key'];
    }

    public function isDebug(): bool
    {
        return $this->config['app']['debug'];
    }

    public function getMaxFileSize(): int
    {
        return $this->config['upload']['max_file_size'];
    }
}
