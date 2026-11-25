<?php
ob_start();

$route = $_GET['route'] ?? '';
$route = trim($route, '/');

if ($route === '') {
    $target = __DIR__ . '/../index.php';
} else {
    $pathOnly = strtok($route, '?');

    // Keamanan: tolak path berbahaya dan file internal (tidak boleh dibuka langsung lewat URL)
    $terlarang = strpos($pathOnly, '..') !== false
              || preg_match('#(^|/)\.#', $pathOnly)
              || preg_match('#^(includes|api)(/|$)#', $pathOnly)
              || $pathOnly === 'inc.php';

    if ($terlarang) {
        http_response_code(404);
        exit('404 Not Found');
    }
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

// Simpan session cookie SEBELUM HTML dikirim ke browser
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

if (ob_get_level() > 0) {
    ob_end_flush();
}