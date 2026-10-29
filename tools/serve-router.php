<?php
// Router for PHP's built-in server (development only):
//   php -S localhost:8080 -t public tools/serve-router.php
$path = urldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$file = __DIR__ . '/../public' . $path;
if ($path !== '/' && is_file($file)) {
    return false; // let the server stream static assets
}
require __DIR__ . '/../public/index.php';
