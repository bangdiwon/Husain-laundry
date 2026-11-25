<?php
require 'inc.php';

// ---------- 1. Pelanggan mengirim review ----------
if (($_POST['aksi'] ?? '') === 'review') {
    $pesanan = cari_pesanan(normal_kode($_POST['kode'] ?? ''));

    if (!$pesanan) {
        flash('Pesanan tidak ditemukan.', 'danger');
        go('index.php#lacak');
    }

    [$berhasil, $pesan] = simpan_review($pesanan);

    flash($pesan, $berhasil ? 'success' : 'danger');
    go('index.php?kode=' . urlencode($pesanan['kode']) . '#lacak');
}

// ---------- 2. Pelanggan melacak pesanan ----------
$kodeInput = trim($_GET['kode'] ?? '');
$pesanan   = null;
$galat     = '';

if ($kodeInput !== '') {
    if (!batas_lacak()) {
        $galat = 'Terlalu banyak percobaan. Silakan coba lagi dalam beberapa menit.';
    } else {
        $kode    = normal_kode($kodeInput);
        $pesanan = $kode ? cari_pesanan($kode) : null;

        if (!$pesanan) {
            $galat = 'Kode pesanan tidak ditemukan. Periksa kembali kode pada struk Anda.';
        }
    }
}

// Data tambahan bila pesanan ditemukan
$item   = [];
$review = null;
$promoReview = null;

if ($pesanan) {
    $item   = q("SELECT * FROM transaksi_item WHERE transaksi_id=?", [$pesanan['id']])->fetchAll();
    $review = ambil_review($pesanan['id']);

    if ($review && $review['promo_id']) {
        $promoReview = q("SELECT * FROM promo WHERE id=?", [$review['promo_id']])->fetch();
    }
}

// ---------- 3. Data halaman ----------
$layanan = q("SELECT * FROM layanan WHERE aktif=1 ORDER BY nama")->fetchAll();

$keunggulan = [
    ['bi-stopwatch',   'Tepat waktu',         'Cucian selesai sesuai janji, dan statusnya bisa Anda pantau.'],
    ['bi-stars',       'Bersih dan wangi',    'Dicuci dengan teliti, disetrika rapi, dan dilipat siap pakai.'],
    ['bi-cash-coin',   'Harga transparan',    'Harga per kg atau per satuan jelas, tanpa biaya tersembunyi.'],
    ['bi-geo-alt',     'Bisa dilacak online', 'Cukup masukkan kode pesanan, tanpa daftar dan tanpa login.'],
];

$langkah = [
    ['bi-bag-check',   'Antar cucian',    'Serahkan cucian ke kasir, Anda menerima struk berisi kode pesanan.'],
    ['bi-search',      'Lacak pesanan',   'Masukkan kode pesanan di website untuk melihat statusnya.'],
    ['bi-check2-circle', 'Pesanan selesai', 'Status berubah Selesai, Anda tinggal mengambil cucian.'],
    ['bi-gift',        'Isi review',      'Jawab beberapa pertanyaan singkat dan dapatkan kode promo.'],
];

head('Beranda', null, 'full');
?>

<!-- ============ BERANDA ============ -->
<section class="hero2" id="beranda">
  <div class="container">
    <div class="row align-items-center g-5">

      <div class="col-lg-6">
        <h1>Cucian bersih, rapi, dan bisa Anda lacak dari mana saja.</h1>

        <p class="lead my-4">
          Husain Laundry melayani cuci kiloan dan satuan. Pantau pesanan Anda
          dengan kode pesanan, tanpa perlu daftar atau login.
        </p>

        <a href="#lacak" class="btn btn-sun btn-lg px-4 me-2">Lacak pesanan</a>
        <a href="#layanan" class="btn btn-outline-light btn-lg px-4">Lihat layanan</a>
      </div>

      <!-- Contoh tampilan hasil lacak -->
      <div class="col-lg-5 offset-lg-1">
        <div class="mock">
          <div class="d-flex justify-content-between mb-1">
            <b>HL00001</b>
            <span class="live"><i class="dot"></i>Live</span>
          </div>

          <div class="text-muted small mb-4">Cuci Setrika x4, Bed Cover x1</div>

          <?= tracker('Proses') ?>

          <div class="alert alert-primary mt-4 mb-0 py-2 small">
            <i class="bi bi-hourglass-split me-1"></i>Pesanan Anda sedang diproses.
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ============ TENTANG ============ -->
<section class="section" id="tentang">
  <div class="container">

    <div class="row g-5 align-items-center mb-5">
      <div class="col-lg-6">
        <h2 class="mb-3">Tentang Husain Laundry</h2>
        <p class="text-muted">
          Husain Laundry adalah usaha laundry yang mengutamakan kebersihan, ketepatan waktu,
          dan keterbukaan kepada pelanggan. Setiap pesanan dicatat di sistem sehingga
          pelanggan dapat mengetahui sampai tahap mana cucian mereka dikerjakan.
        </p>
        <p class="text-muted mb-0">
          Kami juga ingin terus berbenah. Karena itu, setelah pesanan selesai,
          pelanggan kami ajak mengisi review singkat sebagai bahan evaluasi pelayanan,
          dan sebagai ucapan terima kasih kami berikan kode promo.
        </p>
      </div>

      <div class="col-lg-6">
        <div class="row g-3">
          <?php foreach ($keunggulan as [$ikon, $judul, $isi]): ?>
            <div class="col-sm-6">
              <div class="card p-3 h-100">
                <div class="ico mb-2"><i class="bi <?= $ikon ?>"></i></div>
                <h6 class="mb-1"><?= $judul ?></h6>
                <div class="text-muted small"><?= $isi ?></div>
              </div>
            </div>
          <?php endforeach ?>
        </div>
      </div>
    </div>

    <h3 class="text-center mb-4">Cara kerjanya</h3>
    <div class="row g-3">
      <?php foreach ($langkah as $no => [$ikon, $judul, $isi]): ?>
        <div class="col-md-3">
          <div class="card p-4 h-100">
            <div class="ico mb-3"><i class="bi <?= $ikon ?>"></i></div>
            <h5><?= $no + 1 ?>. <?= $judul ?></h5>
            <div class="text-muted small"><?= $isi ?></div>
          </div>
        </div>
      <?php endforeach ?>
    </div>

  </div>
</section>

<!-- ============ LAYANAN ============ -->
<section class="section pt-0" id="layanan">
  <div class="container">
    <h2 class="mb-1">Layanan & harga</h2>
    <p class="text-muted mb-4">Harga transparan, per kilogram atau per satuan.</p>

    <div class="row g-3">
      <?php foreach ($layanan as $l): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card p-4 h-100">
            <div class="job">

              <div class="jico">
                <i class="bi <?= $l['satuan'] == 'kg' ? 'bi-basket3' : 'bi-tag' ?>"></i>
              </div>

              <div>
                <h5 class="mb-1"><?= e($l['nama']) ?></h5>
                <div class="text-muted small mb-2"><?= e($l['deskripsi']) ?></div>
                <div class="fs-4 fw-bold" style="color:var(--blue)">
                  <?= rp($l['harga']) ?>
                  <small class="fs-6 text-muted fw-normal">/ <?= $l['satuan'] ?></small>
                </div>
              </div>

            </div>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  </div>
</section>

<!-- ============ LACAK PESANAN ============ -->
<section class="section lacak-bg" id="lacak">
  <div class="container" style="max-width: 820px">

    <div class="text-center mb-4">
      <h2 class="mb-1">Lacak pesanan Anda</h2>
      <p class="text-muted">
        Masukkan kode pesanan yang tertera pada struk, contoh: <b>HL00001</b> (atau cukup angkanya)
      </p>
    </div>

    <?= flash() ?>

    <!-- Form lacak -->
    <form method="get" action="index.php#lacak" class="card p-3 mb-4">
      <div class="input-group input-group-lg">
        <input name="kode" class="form-control" placeholder="Masukkan kode pesanan"
               value="<?= e($kodeInput) ?>" maxlength="20" required>
        <button class="btn btn-primary px-4"><i class="bi bi-search me-1"></i>Lacak</button>
      </div>
    </form>

    <!-- Kode tidak ditemukan -->
    <?php if ($galat): ?>
      <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-1"></i><?= e($galat) ?></div>
    <?php endif ?>

    <!-- Hasil pelacakan (diperbarui otomatis saat status berubah) -->
    <?php if ($pesanan): ?>
      <div id="hasil-lacak" data-kode="<?= e($pesanan['kode']) ?>"
           data-sig="<?= e($pesanan['status'] . ':' . ($review['id'] ?? 0) . ':' . md5($review['balasan'] ?? '')) ?>">

        <div class="card p-4 mb-3">

          <div class="d-flex justify-content-between flex-wrap gap-2 mb-1">
            <div>
              <div class="text-muted small">Kode pesanan</div>
              <h4 class="mb-0"><?= e($pesanan['kode']) ?></h4>
            </div>
            <div class="text-end">
              <?= badge($pesanan['status']) ?>
              <div class="text-muted small mt-1">
                <span class="live"><i class="dot"></i>Diperbarui otomatis</span>
              </div>
            </div>
          </div>

          <div class="text-muted small mb-4">
            Atas nama <?= e(samarkan_nama($pesanan['pelanggan'])) ?>
            | Masuk <?= tgl($pesanan['tgl_masuk']) ?>
          </div>

          <?= tracker($pesanan['status']) ?>

          <!-- Rincian barang -->
          <div class="mt-4">
            <?php foreach ($item as $i): ?>
              <div class="d-flex justify-content-between small py-2 border-top">
                <span><?= e($i['nama']) ?> <span class="text-muted">x<?= (float)$i['jumlah'] ?></span></span>
                <span><?= rp($i['subtotal']) ?></span>
              </div>
            <?php endforeach ?>

            <div class="d-flex justify-content-between border-top pt-2">
              <span class="fw-semibold">
                Total
                <?php if ($pesanan['diskon_persen']): ?>
                  <small class="text-muted fw-normal">(diskon <?= $pesanan['diskon_persen'] ?>%)</small>
                <?php endif ?>
              </span>
              <span class="fw-bold fs-5"><?= rp($pesanan['total']) ?></span>
            </div>
          </div>

          <a href="struk.php?kode=<?= urlencode($pesanan['kode']) ?>"
             class="btn btn-sm btn-outline-secondary mt-3 align-self-start">
            <i class="bi bi-receipt"></i> Lihat struk
          </a>

        </div>

        <!-- ---------- Bagian review & kode promo ---------- -->
        <?php if (!boleh_review($pesanan)): ?>

          <div class="alert alert-info">
            <i class="bi bi-info-circle me-1"></i>
            Setelah pesanan selesai, Anda bisa mengisi review singkat dan mendapatkan kode promo.
          </div>

        <?php elseif (!$review): ?>

          <div class="card p-4 text-center review-ajak">
            <div class="ico mx-auto mb-2"><i class="bi bi-gift"></i></div>
            <h5>Pesanan Anda sudah selesai</h5>
            <p class="text-muted mb-3">
              Bantu kami menjadi lebih baik. Isi review singkat dan dapatkan kode promo
              untuk transaksi berikutnya.
            </p>
            <div>
              <button type="button" class="btn btn-sun btn-lg px-4" data-buka-review>
                <i class="bi bi-pencil-square me-1"></i>Isi review & dapatkan kode promo
              </button>
            </div>
          </div>

        <?php else: ?>

          <div class="card p-4 text-center">
            <h5 class="mb-1"><i class="bi bi-patch-check-fill text-success me-1"></i>Terima kasih atas review Anda</h5>

            <?php if ($promoReview): ?>
              <p class="text-muted">Ini kode promo untuk Anda:</p>

              <div class="voucher mx-auto">
                <div class="small text-muted"><?= e($promoReview['judul']) ?> | Diskon <?= $promoReview['diskon_persen'] ?>%</div>
                <div class="kode"><?= e($promoReview['kode']) ?></div>
                <div class="small text-muted">
                  <?= $promoReview['berlaku_sampai']
                      ? 'Berlaku sampai ' . date('d/m/Y', strtotime($promoReview['berlaku_sampai']))
                      : 'Tanpa batas waktu' ?>
                </div>
              </div>

              <?php if ($review['promo_dipakai']): ?>
                <div class="text-muted small mt-3">Kode promo ini sudah digunakan.</div>
              <?php else: ?>
                <div class="text-muted small mt-3">
                  Sebutkan kode ini kepada kasir saat transaksi berikutnya (berlaku satu kali).
                </div>
              <?php endif ?>
            <?php endif ?>

            <?php if ($review['balasan']): ?>
              <div class="balasan mt-3 text-start">
                <b><i class="bi bi-reply-fill"></i> Balasan Husain Laundry</b>
                <div><?= nl2br(e($review['balasan'])) ?></div>
              </div>
            <?php endif ?>
          </div>

        <?php endif ?>

      </div>

      <!-- ---------- Form review (jendela pop-up) ----------
           Selalu disiapkan agar tombol tetap berfungsi saat status berubah
           menjadi Selesai ketika halaman sedang terbuka. -->
      <?php if (!$review): ?>
        <div class="modal fade" id="modalReview" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">

            <!-- Kelas modal-content dipasang pada <form> supaya isi form bisa di-scroll
                 dan tombol "Kirim review" selalu terlihat di bagian bawah. -->
            <form method="post" action="index.php" class="modal-content">
              <input type="hidden" name="aksi" value="review">
              <input type="hidden" name="kode" value="<?= e($pesanan['kode']) ?>">
              <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

              <div class="modal-header">
                <h5 class="modal-title">Review pelayanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>

              <div class="modal-body">
                <p class="text-muted small mb-1">
                  Jawab semua pertanyaan untuk mendapatkan kode promo.
                </p>

                <div class="tanya">
                  <span>1. Seberapa bersih hasil cucian Anda?</span>
                  <?php input_bintang('r_kebersihan') ?>
                </div>

                <div class="tanya">
                  <span>2. Seberapa tepat waktu pesanan selesai?</span>
                  <?php input_bintang('r_waktu') ?>
                </div>

                <div class="tanya">
                  <span>3. Bagaimana keramahan dan pelayanan kami?</span>
                  <?php input_bintang('r_pelayanan') ?>
                </div>

                <div class="tanya">
                  <span>4. Apakah harga sesuai dengan kualitas?</span>
                  <?php input_bintang('r_harga') ?>
                </div>

                <div class="tanya">
                  <span>5. Apakah Anda akan merekomendasikan kami?</span>

                  <div class="btn-group btn-group-sm">
                    <input type="radio" class="btn-check" name="rekomendasi" id="rekYa" value="Ya" required>
                    <label class="btn btn-outline-primary" for="rekYa">Ya</label>

                    <input type="radio" class="btn-check" name="rekomendasi" id="rekTidak" value="Tidak">
                    <label class="btn btn-outline-primary" for="rekTidak">Tidak</label>
                  </div>
                </div>

                <label class="fw-semibold small mt-2 mb-1">6. Saran atau keluhan (boleh dikosongkan)</label>
                <textarea name="komentar" class="form-control" rows="2" maxlength="1000"
                          placeholder="Tulis masukan Anda"></textarea>
              </div>

              <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Nanti saja</button>
                <button class="btn btn-primary">Kirim review</button>
              </div>
            </form>

          </div>
        </div>
      <?php endif ?>

    <?php endif ?>

  </div>
</section>

<!-- ============ KONTAK ============ -->
<section class="section pb-4" id="kontak">
  <div class="container">
    <div class="card p-4 p-md-5">
      <div class="row g-4">

        <div class="col-md-4">
          <div class="ico mb-2"><i class="bi bi-geo-alt"></i></div>
          <h6>Alamat</h6>
          <div class="text-muted"><?= e(ALAMAT_USAHA) ?></div>
        </div>

        <div class="col-md-4">
          <div class="ico mb-2"><i class="bi bi-telephone"></i></div>
          <h6>Telepon / WhatsApp</h6>
          <div class="text-muted"><?= e(TELP_USAHA) ?></div>
        </div>

        <div class="col-md-4">
          <div class="ico mb-2"><i class="bi bi-clock"></i></div>
          <h6>Jam buka</h6>
          <div class="text-muted"><?= e(JAM_BUKA) ?></div>
        </div>

      </div>
    </div>

    <div class="text-center text-muted small py-4">
      &copy; <?= date('Y') ?> Husain Laundry. Sistem E-CRM
    </div>
  </div>
</section>
<?php foot();
