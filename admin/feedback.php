<?php
require '../inc.php';
guard('admin');

// ---------- 1. HAPUS ----------
if (isset($_POST['del'])) {
    q("DELETE FROM review WHERE id=?", [$_POST['del']]);
    flash('Review dihapus.');
    go('feedback.php');
}

// ---------- 2. BALAS (balasan tampil di halaman lacak pelanggan) ----------
if (isset($_POST['balas'])) {
    q("UPDATE review SET balasan=? WHERE id=?", [trim($_POST['balasan']), $_POST['id']]);

    flash('Review diperbarui.');
    go('feedback.php');
}

// ---------- 3. DATA TAMPILAN (yang belum dibalas tampil paling atas) ----------
$daftar = q("SELECT r.*, p.nama pelanggan, t.kode kode_pesanan, pr.kode kode_promo
             FROM review r
             JOIN pelanggan p ON p.id = r.pelanggan_id
             JOIN transaksi t ON t.id = r.transaksi_id
             LEFT JOIN promo pr ON pr.id = r.promo_id
             ORDER BY (r.balasan IS NULL OR r.balasan = '') DESC, r.id DESC")->fetchAll();

// Pertanyaan bintang: [kolom, label]
$aspek = [
    ['r_kebersihan', 'Kebersihan'],
    ['r_waktu',      'Ketepatan waktu'],
    ['r_pelayanan',  'Pelayanan'],
    ['r_harga',      'Harga'],
];

head('Umpan Balik', 'admin');
?>
<h3 class="mb-3">Review pelanggan</h3>

<?php foreach ($daftar as $r):
    $rata = ($r['r_kebersihan'] + $r['r_waktu'] + $r['r_pelayanan'] + $r['r_harga']) / 4;
?>
  <div class="card p-3 mb-3">

    <!-- Identitas -->
    <div class="d-flex justify-content-between flex-wrap gap-1">
      <b>
        <?= e($r['pelanggan']) ?>
        <small class="text-muted">(<?= e($r['kode_pesanan']) ?>)</small>
      </b>
      <small class="text-muted"><?= tgl($r['created_at']) ?></small>
    </div>

    <!-- Nilai tiap pertanyaan -->
    <div class="row g-2 my-2">
      <?php foreach ($aspek as [$kolom, $label]): ?>
        <div class="col-6 col-md-3">
          <div class="small text-muted"><?= $label ?></div>
          <span class="text-warning"><?= stars($r[$kolom]) ?></span>
        </div>
      <?php endforeach ?>
    </div>

    <div class="small mb-2">
      Rata-rata <b><?= number_format($rata, 1) ?></b> / 5 |
      Merekomendasikan: <b><?= $r['rekomendasi'] ?></b>
      <?php if ($r['kode_promo']): ?>
        | Kode promo: <b><?= e($r['kode_promo']) ?></b>
        (<?= $r['promo_dipakai'] ? 'sudah dipakai' : 'belum dipakai' ?>)
      <?php endif ?>
    </div>

    <?php if ($r['komentar']): ?>
      <p class="my-2"><?= nl2br(e($r['komentar'])) ?></p>
    <?php endif ?>

    <!-- Balasan -->
    <form method="post" class="row g-2">
      <input type="hidden" name="id" value="<?= $r['id'] ?>">

      <div class="col-md-9">
        <input name="balasan" class="form-control" placeholder="Tulis balasan (tampil ke pelanggan)"
               value="<?= e($r['balasan']) ?>">
      </div>

      <div class="col-md-3 text-nowrap">
        <button name="balas" class="btn btn-primary">Simpan balasan</button>
      </div>
    </form>

    <div class="mt-2"><?= delform($r['id']) ?></div>

  </div>
<?php endforeach ?>

<?php if (!$daftar): ?>
  <div class="text-muted">Belum ada review.</div>
<?php endif ?>
<?php foot();
