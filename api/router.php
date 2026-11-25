<?php
ob_start();

$route = $_GET['route'] ?? '';
$route = trim($route, '/');

if ($route === '') {
    $target = __DIR__ . '/../index.php';
} else {
    $pathOnly = strtok($route, '?');
    $target = __DIR__ . '/../' . $pathOnly;

    if (is_dir($target)) {
        $target = rtrim($target, '/') . '/index.php';
    }
}

if (file_exists($target) && pathinfo($target, PATHINFO_EXTENSION) === 'php') {
    chdir(dirname($target));
    require $target;
} else {
    http_response_code(404);
    echo "404 Not Found: " . htmlspecialchars($route);
}

ob_end_flush();