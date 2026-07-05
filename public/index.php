<?php declare(strict_types=1);

use Kniebes\MovieTracker\Bootstrap\Environment;
use Kniebes\MovieTracker\Controller\AuthController;
use Kniebes\MovieTracker\Controller\CastController;
use Kniebes\MovieTracker\Controller\MovieController;
use Kniebes\MovieTracker\Http\Response;
use Kniebes\MovieTracker\Service\Auth;
use Kniebes\MovieTracker\Service\ErrorHandler;

// PHP-Built-in-Server: vorhandene Dateien (Assets) direkt ausliefern
if (PHP_SAPI === 'cli-server') {
    $requestedFile = __DIR__ . parse_url($_SERVER['REQUEST_URI'], component: PHP_URL_PATH);
    if (is_file($requestedFile)) {
        return false;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    Environment::init(dirname(__DIR__));
} catch (Throwable $throwable) {
    ErrorHandler::handle($throwable);
}

$path = rtrim((string) parse_url($_SERVER['REQUEST_URI'], component: PHP_URL_PATH), characters: '/');
if ($path === '') {
    $path = '/';
}
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($path !== '/login' && !(new Auth())->isAuthenticated()) {
        Response::redirect('/login');
    }
} catch (Throwable $throwable) {
    ErrorHandler::handle($throwable);
}

$routes = [
    ['GET', '#^/login$#', fn () => (new AuthController())->loginForm()],
    ['POST', '#^/login$#', fn () => (new AuthController())->login()],
    ['POST', '#^/logout$#', fn () => (new AuthController())->logout()],

    ['GET', '#^/$#', fn () => Response::redirect('/movies')],
    ['GET', '#^/movies$#', fn () => (new MovieController())->list()],
    ['GET', '#^/movies/new$#', fn () => (new MovieController())->createForm()],
    ['POST', '#^/movies$#', fn () => (new MovieController())->store()],
    ['GET', '#^/movies/(\d+)/edit$#', fn (int $id) => (new MovieController())->editForm($id)],
    ['POST', '#^/movies/(\d+)$#', fn (int $id) => (new MovieController())->update($id)],
    ['DELETE', '#^/movies/(\d+)$#', fn (int $id) => (new MovieController())->delete($id)],
    ['POST', '#^/tmdb-lookup$#', fn () => (new MovieController())->tmdbLookup()],

    ['GET', '#^/cast$#', fn () => (new CastController())->list()],
    ['GET', '#^/cast/(\d+)$#', fn (int $id) => (new CastController())->row($id)],
    ['GET', '#^/cast/(\d+)/edit$#', fn (int $id) => (new CastController())->editRow($id)],
    ['PUT', '#^/cast/(\d+)$#', fn (int $id) => (new CastController())->update($id)],
    ['DELETE', '#^/cast/(\d+)$#', fn (int $id) => (new CastController())->delete($id)],
];

try {
    foreach ($routes as [$routeMethod, $pattern, $handler]) {
        if ($routeMethod === $method && preg_match($pattern, $path, $matches)) {
            $handler(...array_map(intval(...), array_slice($matches, offset: 1)));
        }
    }

    Response::notFound();
} catch (Throwable $throwable) {
    ErrorHandler::handle($throwable);
}
