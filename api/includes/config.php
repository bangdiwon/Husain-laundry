<?php
// ============================================================
// PENGATURAN UTAMA - ubah di sini bila perlu
// ============================================================

// Koneksi database (default XAMPP: user root, password kosong)
$pdo = new PDO('mysql:host=localhost;dbname=husain_laundry;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Tahapan status cucian (urutan = alur proses)
const STATUS = ['Antri', 'Proses', 'Selesai', 'Ambil'];

// Token Fonnte untuk notifikasi WhatsApp otomatis.
// Kosongkan jika belum dipakai (tombol WA manual di admin tetap berfungsi).
const WA_TOKEN = '';

// Identitas usaha (tampil di struk dan laporan)
const NAMA_USAHA   = 'Husain Laundry';
const ALAMAT_USAHA = 'Jl. Contoh No. 1, Jakarta';
const TELP_USAHA   = '0812-3456-7890';
