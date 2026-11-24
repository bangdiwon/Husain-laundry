<?php
// ============================================================
// ALUR PELANGGAN (tanpa login)
//   lacak pesanan > pesanan selesai > isi review > dapat kode promo
// ============================================================

// Cari pesanan berdasarkan kode pesanan
function cari_pesanan($kode) {
    return q("SELECT t.*, p.nama pelanggan, p.no_hp
              FROM transaksi t
              JOIN pelanggan p ON p.id = t.pelanggan_id
              WHERE t.kode = ?", [$kode])->fetch();
}

// Batasi percobaan lacak: maksimal 20 kali per 10 menit (mencegah tebak-tebak kode)
function batas_lacak() {
    $catatan = $_SESSION['lacak'] ?? ['jumlah' => 0, 'mulai' => time()];

    if (time() - $catatan['mulai'] > 600) {
        $catatan = ['jumlah' => 0, 'mulai' => time()];
    }

    $catatan['jumlah']++;
    $_SESSION['lacak'] = $catatan;

    return $catatan['jumlah'] <= 20;
}

// Samarkan nama di halaman publik: "Budi Santoso" menjadi "Budi S*****"
function samarkan_nama($nama) {
    $kata  = explode(' ', trim($nama));
    $hasil = [$kata[0]];

    foreach (array_slice($kata, 1) as $k) {
        $hasil[] = mb_substr($k, 0, 1) . str_repeat('*', max(2, mb_strlen($k) - 1));
    }

    return implode(' ', $hasil);
}

// Review hanya boleh diisi setelah pesanan selesai
function boleh_review($pesanan) {
    return in_array($pesanan['status'], ['Selesai', 'Ambil']);
}

function ambil_review($transaksiId) {
    return q("SELECT * FROM review WHERE transaksi_id=?", [$transaksiId])->fetch();
}

// Pilih kode promo buatan admin untuk hadiah review (jika ada beberapa, dipilih acak)
function pilih_promo_review() {
    return q("SELECT * FROM promo
              WHERE aktif = 1 AND untuk_review = 1
                AND (berlaku_sampai IS NULL OR berlaku_sampai >= CURDATE())
              ORDER BY RAND()
              LIMIT 1")->fetch();
}

// Simpan review dari form. Hasil: [berhasil, pesan]
// Kode promo HANYA diberikan jika semua pertanyaan dijawab.
function simpan_review($pesanan) {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        return [false, 'Permintaan tidak valid. Muat ulang halaman lalu coba lagi.'];
    }

    if (!boleh_review($pesanan)) {
        return [false, 'Review baru bisa diisi setelah pesanan selesai.'];
    }

    if (ambil_review($pesanan['id'])) {
        return [false, 'Pesanan ini sudah pernah direview.'];
    }

    $kurang = 'Jawab semua pertanyaan terlebih dahulu untuk mendapatkan kode promo.';

    // Pertanyaan bintang 1-5 (wajib semua)
    $nilai = [];

    foreach (['r_kebersihan', 'r_waktu', 'r_pelayanan', 'r_harga'] as $kolom) {
        $angka = (int)($_POST[$kolom] ?? 0);

        if ($angka < 1 || $angka > 5) {
            return [false, $kurang];
        }

        $nilai[$kolom] = $angka;
    }

    // Pertanyaan ya / tidak (wajib)
    $rekomendasi = $_POST['rekomendasi'] ?? '';

    if (!in_array($rekomendasi, ['Ya', 'Tidak'])) {
        return [false, $kurang];
    }

    $komentar = mb_substr(trim($_POST['komentar'] ?? ''), 0, 1000);   // opsional
    $promo    = pilih_promo_review();

    try {
        q("INSERT INTO review(transaksi_id, pelanggan_id, r_kebersihan, r_waktu, r_pelayanan,
                              r_harga, rekomendasi, komentar, promo_id)
           VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)",
          [
              $pesanan['id'], $pesanan['pelanggan_id'],
              $nilai['r_kebersihan'], $nilai['r_waktu'], $nilai['r_pelayanan'], $nilai['r_harga'],
              $rekomendasi, $komentar, $promo['id'] ?? null,
          ]);
    } catch (PDOException $e) {
        return [false, 'Pesanan ini sudah pernah direview.'];
    }

    if ($promo) {
        return [true, 'Terima kasih atas review Anda! Kode promo Anda: ' . $promo['kode']];
    }

    return [true, 'Terima kasih atas review Anda!'];
}

// Cek kode promo yang diketik admin saat input transaksi.
// Valid bila: kode ada & aktif & belum kedaluwarsa, DIBERIKAN kepada pelanggan ini
// lewat review, dan belum dipakai (kecuali dipakai oleh transaksi yang sedang diedit).
function cek_promo($kode, $pelangganId, $transaksiId = 0) {
    return q("SELECT r.id AS review_id, p.*
              FROM review r
              JOIN promo p ON p.id = r.promo_id
              WHERE p.kode = ?
                AND r.pelanggan_id = ?
                AND p.aktif = 1
                AND (p.berlaku_sampai IS NULL OR p.berlaku_sampai >= CURDATE())
                AND (r.promo_dipakai = 0 OR r.dipakai_transaksi_id = ?)
              LIMIT 1", [$kode, $pelangganId, $transaksiId])->fetch();
}

// Bintang untuk form review (radio disembunyikan, label berbentuk bintang)
function input_bintang($nama) {
    echo "<div class='rate'>";

    for ($i = 5; $i >= 1; $i--) {
        echo "<input type='radio' name='$nama' id='{$nama}_$i' value='$i' required>";
        echo "<label for='{$nama}_$i'>★</label>";
    }

    echo "</div>";
}
