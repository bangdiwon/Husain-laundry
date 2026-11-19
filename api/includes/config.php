<?php
// =========================================================
// PENGATURAN UTAMA - ubah di sini bila perlu
// =========================================================

// Koneksi database (Otomatis menggunakan TiDB Cloud di Vercel, atau localhost di XAMPP)
$db_host = getenv('DB_HOST') ?: 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$db_port = getenv('DB_PORT') ?: '4000';
$db_name = getenv('DB_NAME') ?: 'husain_laundry';
$db_user = getenv('DB_USER') ?: '2DSoiidmzPqky8W.root';
$db_pass = getenv('DB_PASS') ?: '';

$pdo_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Aktifkan sertifikat SSL wajib untuk koneksi ke TiDB Cloud saat di Vercel
if ($db_host !== '127.0.0.1' && $db_host !== 'localhost') {
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

// Identitas usaha (tampil di struk dan laporan)
const NAMA_USAHA   = 'Husain Laundry';
const ALAMAT_USAHA = 'Jl. Contoh No. 1, Jakarta';
const TELP_USAHA   = '0812-3456-7890';