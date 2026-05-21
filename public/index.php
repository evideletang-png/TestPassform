<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($requestPath === '/_ping') {
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => true,
        'php' => PHP_VERSION,
        'time' => date(DATE_ATOM),
        'entrypoint' => 'public/index.php',
    ]);
    exit;
}

if ($requestPath === '/_preflight') {
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => true,
        'php' => PHP_VERSION,
        'app_key_set' => !empty($_ENV['APP_KEY'] ?? getenv('APP_KEY')),
        'app_url' => $_ENV['APP_URL'] ?? getenv('APP_URL') ?: null,
        'log_channel' => $_ENV['LOG_CHANNEL'] ?? getenv('LOG_CHANNEL') ?: null,
        'cache_store' => $_ENV['CACHE_STORE'] ?? getenv('CACHE_STORE') ?: null,
        'session_driver' => $_ENV['SESSION_DRIVER'] ?? getenv('SESSION_DRIVER') ?: null,
        'db_connection' => $_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: null,
        'db_host_set' => !empty($_ENV['DB_HOST'] ?? getenv('DB_HOST')),
        'db_database_set' => !empty($_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE')),
        'db_username_set' => !empty($_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME')),
        'db_password_set' => !empty($_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD')),
    ]);
    exit;
}

if ($requestPath === '/_bootstrap-check') {
    header('Content-Type: application/json');

    try {
        require __DIR__.'/../vendor/autoload.php';

        $app = require __DIR__.'/../bootstrap/app.php';

        echo json_encode([
            'ok' => true,
            'autoload' => true,
            'bootstrap' => true,
            'app_key_set' => !empty($_ENV['APP_KEY'] ?? getenv('APP_KEY')),
            'base_path' => method_exists($app, 'basePath') ? $app->basePath() : null,
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error_class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }

    exit;
}

if ($requestPath === '/_filament-check') {
    header('Content-Type: application/json');

    try {
        require __DIR__.'/../vendor/autoload.php';

        $checks = [
            App\Providers\Filament\AdminPanelProvider::class,
            App\Filament\Resources\SessionFormationResource::class,
            App\Filament\Resources\SessionFormationResource\Pages\ListSessionFormations::class,
            App\Filament\Resources\SessionFormationResource\Pages\CreateSessionFormation::class,
            App\Filament\Resources\SessionFormationResource\Pages\ViewSessionFormation::class,
            App\Filament\Resources\SessionFormationResource\Pages\EditSessionFormation::class,
            App\Filament\Resources\UserResource::class,
            App\Filament\Resources\UserResource\Pages\ListUsers::class,
            App\Filament\Resources\UserResource\Pages\CreateUser::class,
            App\Filament\Resources\UserResource\Pages\EditUser::class,
            App\Filament\Pages\Parametres::class,
            App\Filament\Widgets\StatsOverview::class,
            App\Filament\Widgets\SessionsTable::class,
        ];

        $loaded = [];
        foreach ($checks as $class) {
            $loaded[$class] = class_exists($class);
        }

        echo json_encode([
            'ok' => !in_array(false, $loaded, true),
            'classes' => $loaded,
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error_class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }

    exit;
}

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
