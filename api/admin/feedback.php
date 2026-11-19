<?php
require '../inc.php';
guard('admin');

// ---------- 1. HAPUS ----------
if (isset($_POST['del'])) {
    q("DELETE FROM feedback WHERE id=?", [$_POST['del']]);
    flash('Umpan balik dihapus.');
    go('feedback.php');
}

// ---------- 2. BALAS + UBAH STATUS ----------
if (isset($_POST['balas'])) {
    $f = q("SELECT * FROM feedback WHERE id=?", [$_POST['id']])->fetch();

    if ($f) {
        $balasan = trim($_POST['balasan']);

        q("UPDATE feedback SET balasan=?, status=? WHERE id=?",
          [$balasan, $_POST['status'], $f['id']]);

        // Pelanggan diberi tahu hanya jika balasannya baru / berubah
        if ($balasan !== '' && $balasan !== (string)$f['balasan']) {
            notif($f['user_id'], 'Husain Laundry membalas masukan Anda. Lihat di menu Feedback.');
        }

        flash('Umpan balik diperbarui.');
    }

    go('feedback.php');
}

// ---------- 3. DATA TAMPILAN (yang baru tampil paling atas) ----------
$daftar = q("SELECT f.*, u.nama pelanggan, t.kode
             FROM feedback f
             JOIN users u ON u.id = f.user_id
             LEFT JOIN transaksi t ON t.id = f.transaksi_id
             ORDER BY FIELD(f.status, 'Baru', 'Diproses', 'Selesai'), f.id DESC")->fetchAll();

head('Umpan Balik', 'admin');
?>
<h3 class="mb-3">Saran & keluhan pelanggan</h3>

<?php foreach ($daftar as $f): ?>
  <div class="card p-3 mb-3">

    <div class="d-flex justify-content-between flex-wrap gap-1">
      <b>
        <?= e($f['pelanggan']) ?> - <?= $f['jenis'] ?>
        <span class="text-warning"><?= stars($f['rating']) ?></span>
        <?php if ($f['kode']): ?>
          <small class="text-muted">(<?= e($f['kode']) ?>)</small>
        <?php endif ?>
      </b>
      <small class="text-muted"><?= tgl($f['created_at']) ?></small>
    </div>

    <p class="my-2"><?= nl2br(e($f['pesan'])) ?></p>

    <form method="post" class="row g-2">
      <input type="hidden" name="id" value="<?= $f['id'] ?>">

      <div class="col-md-7">
        <input name="balasan" class="form-control" placeholder="Tulis balasan"
               value="<?= e($f['balasan']) ?>">
      </div>

      <div class="col-md-2">
        <select name="status" class="form-select">
          <?php foreach (['Baru', 'Diproses', 'Selesai'] as $s): ?>
            <option <?= sel($f['status'], $s) ?>><?= $s ?></option>
          <?php endforeach ?>
        </select>
      </div>

      <div class="col-md-3 text-nowrap">
        <button name="balas" class="btn btn-primary">Simpan</button>
      </div>
    </form>

    <div class="mt-2"><?= delform($f['id']) ?></div>

  </div>
<?php endforeach ?>

<?php if (!$daftar): ?>
  <div class="text-muted">Belum ada umpan balik.</div>
<?php endif ?>
<?php foot();
