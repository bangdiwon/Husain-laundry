<?php
// Endpoint real-time: browser bertanya tiap 8 detik apakah ada perubahan data
define('TANPA_AKTIVITAS', true);      // pertanyaan ini tidak boleh memperpanjang sesi login
require 'inc.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$uid = $_SESSION['uid'] ?? 0;

// Sesi habis / belum login
if (!$uid) {
    echo '{"login":false}';
    exit;
}

// ---------- Data untuk pelanggan ----------
if ($_SESSION['role'] == 'customer') {
    echo json_encode([
        'unread' => (int)q("SELECT COUNT(*) FROM notifikasi
                            WHERE user_id=? AND dibaca=0", [$uid])->fetchColumn(),

        'maxid'  => (int)q("SELECT COALESCE(MAX(id), 0) FROM notifikasi
                            WHERE user_id=?", [$uid])->fetchColumn(),

        'items'  => q("SELECT id, pesan FROM notifikasi
                       WHERE user_id=? AND dibaca=0
                       ORDER BY id DESC LIMIT 5", [$uid])->fetchAll(),

        // "Sidik jari" status semua cucian: berubah bila ada status yang berganti
        'sig'    => q("SELECT COALESCE(GROUP_CONCAT(CONCAT(id, status)), '')
                       FROM transaksi WHERE user_id=?", [$uid])->fetchColumn(),
    ]);
    exit;
}

// ---------- Data untuk admin ----------
echo json_encode([
    'baru' => (int)q("SELECT COUNT(*) FROM feedback WHERE status='Baru'")->fetchColumn(),

    'sig'  => q("SELECT CONCAT(
                    (SELECT COUNT(*) FROM transaksi), '-',
                    (SELECT COALESCE(SUM(status + 0), 0) FROM transaksi), '-',
                    (SELECT COUNT(*) FROM feedback), '-',
                    (SELECT COUNT(*) FROM users))")->fetchColumn(),
]);
