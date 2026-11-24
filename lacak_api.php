<?php
// Dipakai halaman lacak untuk memeriksa apakah status pesanan berubah (tanpa refresh)
require 'inc.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$kode    = normal_kode($_GET['kode'] ?? '');
$pesanan = $kode ? cari_pesanan($kode) : null;

if (!$pesanan) {
    echo '{}';
    exit;
}

$review = ambil_review($pesanan['id']);

echo json_encode([
    'status' => $pesanan['status'],
    'sig'    => $pesanan['status'] . ':' . ($review['id'] ?? 0) . ':' . md5($review['balasan'] ?? ''),
]);
