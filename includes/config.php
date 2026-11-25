<?php
// ============================================================
// PENGATURAN UTAMA
//
// Data rahasia (password database, token, kunci sesi) JANGAN ditulis di kode,
// karena kode ikut terunggah ke GitHub.
//
//  - Localhost (XAMPP): memakai nilai bawaan di bawah (root, tanpa password).
//  - Vercel: isi di Settings > Environment Variables:
//      DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, APP_KEY
//      (opsional) WA_TOKEN, URL_SITUS
// ============================================================

$is_vercel = (bool) getenv('VERCEL');

// Di Vercel, database wajib diatur lewat Environment Variables
if ($is_vercel && !getenv('DB_HOST')) {
    http_response_code(500);
    exit('Konfigurasi database belum diatur. Isi Environment Variables '
       . 'DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS di pengaturan proyek Vercel.');
}

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: 'husain_laundry';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

$pdo_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Database cloud (mis. TiDB Cloud) mewajibkan koneksi terenkripsi SSL/TLS
if ($is_vercel || getenv('DB_HOST')) {
    $pdo_options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
}

try {
    $pdo = new PDO(
        "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        $pdo_options
    );
} catch (PDOException $e) {
    error_log('Koneksi database gagal: ' . $e->getMessage());
    http_response_code(500);

    // Rincian galat hanya ditampilkan di localhost
    exit('Tidak dapat terhubung ke database.' . ($is_vercel ? '' : ' ' . $e->getMessage()));
}

// Tahapan status cucian (urutan = alur proses)
const STATUS = ['Antri', 'Proses', 'Selesai', 'Ambil'];

// Token Fonnte untuk notifikasi WhatsApp otomatis (kosong = nonaktif)
define('WA_TOKEN', getenv('WA_TOKEN') ?: '');

// Alamat website (dipakai pada tautan lacak pesanan di pesan WhatsApp).
// Di Vercel sebaiknya isi URL_SITUS dengan domain utama, mis. https://husain-laundry.vercel.app
define('URL_SITUS', getenv('URL_SITUS')
    ?: (getenv('VERCEL_URL') ? 'https://' . getenv('VERCEL_URL') : 'http://localhost/husain_laundry'));

// Identitas usaha (tampil di struk dan laporan)
const NAMA_USAHA   = 'Husain Laundry';
const ALAMAT_USAHA = 'Jl. Contoh No. 1, Jakarta';
const TELP_USAHA   = '0812-3456-7890';
const JAM_BUKA     = 'Setiap hari, 08.00 - 20.00';   // ubah sesuai jam buka sebenarnya
