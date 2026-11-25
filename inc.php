<?php
// Satu pintu masuk: semua halaman cukup memanggil  require 'inc.php';

class VercelCookieSessionHandler implements SessionHandlerInterface {
    private string $key;
    private string $cookieName = 'HL_SESS_DATA';

    public function __construct(string $secretKey) {
        $this->key = $secretKey;
    }

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }
    public function gc(int $max_lifetime): int|false { return 1; }

    public function destroy(string $id): bool {
        setcookie($this->cookieName, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[$this->cookieName]);
        return true;
    }

    public function read(string $id): string|false {
        if (empty($_COOKIE[$this->cookieName])) return '';
        $parts = explode('.', $_COOKIE[$this->cookieName], 2);
        if (count($parts) !== 2) return '';
        [$payload, $sig] = $parts;
        if (!hash_equals(hash_hmac('sha256', $payload, $this->key), $sig)) return '';
        $decoded = base64_decode($payload, true);
        return $decoded !== false ? $decoded : '';
    }

    public function write(string $id, string $data): bool {
        if ($data === '') {
            return $this->destroy($id);
        }
        $payload = base64_encode($data);
        $sig = hash_hmac('sha256', $payload, $this->key);
        $value = $payload . '.' . $sig;
        setcookie($this->cookieName, $value, [
            'expires'  => 0,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[$this->cookieName] = $value;
        return true;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    if (getenv('VERCEL')) {
        // Kunci penanda tangan sesi harus rahasia: pakai APP_KEY, atau turunkan dari DB_PASS
        $secret = getenv('APP_KEY') ?: (getenv('DB_PASS') ? hash('sha256', 'hl-sesi|' . getenv('DB_PASS')) : '');

        if ($secret === '') {
            http_response_code(500);
            exit('APP_KEY belum diatur. Isi Environment Variable APP_KEY (teks acak panjang) di Vercel.');
        }

        session_set_save_handler(new VercelCookieSessionHandler($secret), true);
    } else {
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    }
    session_start();
    register_shutdown_function('session_write_close');
}
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/fungsi.php';
require __DIR__ . '/includes/whatsapp.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/lacak.php';
require __DIR__ . '/includes/struk.php';
require __DIR__ . '/includes/laporan.php';

amankan_sesi();