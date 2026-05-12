<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Build container
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    'settings.displayErrorDetails' => \App\Config\AppConfig::getInstance()->isDebug(),
]);
$container = $containerBuilder->build();

// Create app
AppFactory::setContainer($container);
$app = AppFactory::create();

// Error middleware
$app->addErrorMiddleware(true, true, true);

// Parse JSON body
$app->addBodyParsingMiddleware();

// Register routes
(require __DIR__ . '/../src/Routes/api.php')($app);

$app->run();
