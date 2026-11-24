<?php
// Struk untuk pelanggan: dibuka lewat kode pesanan dari halaman lacak
require 'inc.php';

tampil_struk(0, normal_kode($_GET['kode'] ?? ''));
