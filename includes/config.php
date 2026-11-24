<?php
// ============================================================
// PENGATURAN UTAMA - ubah di sini bila perlu
// ============================================================

// Deteksi otomatis: jika diakses dari Vercel gunakan TiDB Cloud, jika lokal gunakan XAMPP
$is_vercel = (bool) getenv('VERCEL');

$db_host = getenv('DB_HOST') ?: ($is_vercel ? 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com' : 'localhost');
$db_port = getenv('DB_PORT') ?: ($is_vercel ? '4000' : '3306');
$db_name = getenv('DB_NAME') ?: 'husain_laundry';
$db_user = getenv('DB_USER') ?: ($is_vercel ? '2DSoiidmzPqky8W.root' : 'root');
$db_pass = getenv('DB_PASS') ?: ($is_vercel ? 'AoyPQRnAHFgY8GmS' : '');

$pdo_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Aktifkan SSL/TLS wajib untuk koneksi TiDB Cloud
if ($is_vercel || getenv('DB_HOST')) {
    $pdo_options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
}

$pdo = new PDO(
    "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4",
    $db_user,
    $db_pass,
    $pdo_options
);

// Tahapan status cucian (urutan = alur proses)
const STATUS = ['Antri', 'Proses', 'Selesai', 'Ambil'];

// Token Fonnte untuk notifikasi WhatsApp otomatis.
// Kosongkan jika belum dipakai (tombol WA manual di admin tetap berfungsi).
const WA_TOKEN = '';

// Alamat website (dipakai pada tautan lacak pesanan di pesan WhatsApp)
define('URL_SITUS', getenv('VERCEL_URL') ? 'https://' . getenv('VERCEL_URL') : 'http://localhost/husain_laundry');

// Identitas usaha (tampil di struk dan laporan)
const NAMA_USAHA   = 'Husain Laundry';
const ALAMAT_USAHA = 'Jl. Contoh No. 1, Jakarta';
const TELP_USAHA   = '0812-3456-7890';
const JAM_BUKA     = 'Setiap hari, 08.00 - 20.00';   // ubah sesuai jam buka sebenarnya