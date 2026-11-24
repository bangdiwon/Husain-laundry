<?php
// Pembaruan real-time untuk admin: apakah ada review / transaksi baru
define('TANPA_AKTIVITAS', true);      // pertanyaan ini tidak boleh memperpanjang sesi login
require 'inc.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

// Sesi habis / belum login
if (empty($_SESSION['uid'])) {
    echo '{"login":false}';
    exit;
}

echo json_encode([
    'baru' => (int)q("SELECT COUNT(*) FROM review WHERE balasan IS NULL OR balasan = ''")->fetchColumn(),

    // "Sidik jari" data: berubah bila ada transaksi, status, review, atau pelanggan baru
    'sig'  => q("SELECT CONCAT(
                    (SELECT COUNT(*) FROM transaksi), '-',
                    (SELECT COALESCE(SUM(status + 0), 0) FROM transaksi), '-',
                    (SELECT COUNT(*) FROM review), '-',
                    (SELECT COUNT(*) FROM pelanggan))")->fetchColumn(),
]);
