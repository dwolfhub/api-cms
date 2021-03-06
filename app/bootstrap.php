<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$app = new Silex\Application();
$app['env'] = getenv('APPLICATION_ENV');

// Config
$envConfigFileLoc = __DIR__ . '/config/' . $app['env'] . '.yml';
$defaultConfigFileLoc = __DIR__ . '/config/default.yml';
$configFileLoc = file_exists($envConfigFileLoc)? $envConfigFileLoc: $defaultConfigFileLoc;
$app->register(new DerAlex\Silex\YamlConfigServiceProvider($configFileLoc));

// Debug mode
$app['debug'] = $app['config']['debug'];

// Database
$app->register(new Silex\Provider\DoctrineServiceProvider(), [
    'db.options' => $app['config']['database'],
    'encoder.bcrypt' => $app->share(function ($app) {
        return new \Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder(10);
    })
]);

// Logging
$app->register(new Silex\Provider\MonologServiceProvider(), [
    'monolog.logfile' => __DIR__ . '/logs/app.log',
    'monolog.name' => 'api-cms',
    'monolog.level' => 'DEBUG' //todo get from config
]);

// Cache
$app->register(new Silex\Provider\HttpCacheServiceProvider(), [
    'http_cache.cache_dir' => __DIR__ . '/cache/',
]);

// Validation
$app->register(new Silex\Provider\ValidatorServiceProvider());

// Console Commands
//$app->register(new Knp\Provider\ConsoleServiceProvider(), [
//    'console.name'              => $app['config']['name'],
//    'console.version'           => $app['config']['version'],
//    'console.project_directory' => dirname(__DIR__)
//]);

return $app;