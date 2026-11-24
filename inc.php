<?php
// Satu pintu masuk: semua halaman cukup memanggil  require 'inc.php';
// Cookie sesi hilang saat browser ditutup dan tidak bisa dibaca JavaScript
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/fungsi.php';
require __DIR__ . '/includes/whatsapp.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/lacak.php';
require __DIR__ . '/includes/struk.php';
require __DIR__ . '/includes/laporan.php';

amankan_sesi();
