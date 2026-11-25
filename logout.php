<?php
// Jalankan inc.php agar handler sesi (Vercel & XAMPP) termuat dengan benar
require __DIR__ . '/inc.php';

// 1. Kosongkan semua variabel sesi
$_SESSION = [];

// 2. Deteksi apakah akses menggunakan HTTPS (termasuk proxy Vercel)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

// 3. Hapus cookie sesi custom untuk Vercel (HL_SESS_DATA)
setcookie('HL_SESS_DATA', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
unset($_COOKIE['HL_SESS_DATA']);

// 4. Hapus cookie sesi standar PHP untuk XAMPP lokal (PHPSESSID)
$c = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires'  => time() - 3600,
    'path'     => $c['path'],
    'domain'   => $c['domain'],
    'secure'   => $c['secure'],
    'httponly' => $c['httponly'],
    'samesite' => 'Lax',
]);

// 5. Hancurkan sesi dan simpan perubahan sebelum redirect
session_destroy();
session_write_close();

header('Location: login.php');
exit;