<?php
require '../inc.php';
guard('admin');

// ---------- 1. HAPUS (promo yang sudah diberikan ke pelanggan hanya dinonaktifkan) ----------
if (isset($_POST['del'])) {
    $diberikan = q("SELECT COUNT(*) FROM review WHERE promo_id=?", [$_POST['del']])->fetchColumn();

    if ($diberikan > 0) {
        q("UPDATE promo SET aktif=0 WHERE id=?", [$_POST['del']]);
        flash('Kode promo sudah diberikan ke pelanggan, jadi dinonaktifkan (tidak dihapus).', 'warning');
    } else {
        q("DELETE FROM promo WHERE id=?", [$_POST['del']]);
        flash('Promo dihapus.');
    }

    go('promo.php');
}

// ---------- 2. SIMPAN (tambah / edit) ----------
if (isset($_POST['simpan'])) {
    $d      = array_map('trim', $_POST);
    $id     = (int)$d['id'];
    $kode   = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $d['kode']));
    $aktif  = isset($_POST['aktif']) ? 1 : 0;
    $review = isset($_POST['untuk_review']) ? 1 : 0;
    $diskon = max(0, min(100, (int)$d['diskon_persen']));
    $sampai = $d['berlaku_sampai'] ?: null;      // kosong = tanpa batas waktu

    $kembar = q("SELECT id FROM promo WHERE kode=? AND id<>?", [$kode, $id])->fetch();

    if ($kode === '' || $kembar) {
        flash('Kode promo kosong atau sudah dipakai promo lain.', 'danger');
        go('promo.php' . ($id ? "?edit=$id" : ''));
    }

    if ($id) {
        q("UPDATE promo
           SET kode=?, judul=?, deskripsi=?, diskon_persen=?, berlaku_sampai=?, untuk_review=?, aktif=?
           WHERE id=?",
          [$kode, $d['judul'], $d['deskripsi'], $diskon, $sampai, $review, $aktif, $id]);
    } else {
        q("INSERT INTO promo(kode, judul, deskripsi, diskon_persen, berlaku_sampai, untuk_review, aktif)
           VALUES(?, ?, ?, ?, ?, ?, ?)",
          [$kode, $d['judul'], $d['deskripsi'], $diskon, $sampai, $review, $aktif]);
    }

    flash('Promo disimpan.');
    go('promo.php');
}

// ---------- 3. DATA TAMPILAN ----------
$edit = null;
if (isset($_GET['edit'])) {
    $edit = q("SELECT * FROM promo WHERE id=?", [$_GET['edit']])->fetch();
}

$daftar = q("SELECT p.*,
                    (SELECT COUNT(*) FROM review WHERE promo_id = p.id) diberikan,
                    (SELECT COUNT(*) FROM review WHERE promo_id = p.id AND promo_dipakai = 1) dipakai
             FROM promo p
             ORDER BY p.id DESC")->fetchAll();

head('Kode Promo', 'admin');
?>
<div class="row g-4">

  <!-- Form tambah / edit -->
  <div class="col-xl-4">
    <div class="card p-3">
      <h5><?= $edit ? 'Edit kode promo' : 'Buat kode promo' ?></h5>

      <form method="post">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

        <input name="kode" class="form-control mb-2 text-uppercase" placeholder="Kode (mis. TERIMAKASIH10)"
               required maxlength="30" value="<?= e($edit['kode'] ?? '') ?>">

        <input name="judul" class="form-control mb-2" placeholder="Judul promo" required
               value="<?= e($edit['judul'] ?? '') ?>">

        <textarea name="deskripsi" class="form-control mb-2" rows="3"
                  placeholder="Deskripsi"><?= e($edit['deskripsi'] ?? '') ?></textarea>

        <div class="input-group mb-2">
          <input type="number" min="0" max="100" name="diskon_persen" class="form-control"
                 placeholder="Diskon" required value="<?= e($edit['diskon_persen'] ?? '') ?>">
          <span class="input-group-text">%</span>
        </div>

        <label class="form-label small">Berlaku sampai (kosong = tanpa batas)</label>
        <input type="date" name="berlaku_sampai" class="form-control mb-2"
               value="<?= e($edit['berlaku_sampai'] ?? '') ?>">

        <div class="form-check mb-1">
          <input type="checkbox" name="untuk_review" class="form-check-input" id="untukReview"
                 <?= ($edit['untuk_review'] ?? 1) ? 'checked' : '' ?>>
          <label for="untukReview" class="form-check-label">
            Hadiah review (diberikan ke pelanggan yang mengisi review)
          </label>
        </div>

        <div class="form-check mb-3">
          <input type="checkbox" name="aktif" class="form-check-input" id="aktif"
                 <?= ($edit['aktif'] ?? 1) ? 'checked' : '' ?>>
          <label for="aktif" class="form-check-label">Aktif</label>
        </div>

        <button name="simpan" class="btn btn-primary">Simpan</button>
        <?php if ($edit): ?>
          <a href="promo.php" class="btn btn-light">Batal</a>
        <?php endif ?>
      </form>

      <div class="small text-muted mt-3">
        Jika ada lebih dari satu kode "hadiah review" yang aktif, sistem memilih salah satunya secara acak.
      </div>
    </div>
  </div>

  <!-- Daftar promo -->
  <div class="col-xl-8">
    <div class="card table-responsive">
      <table class="table rtable mb-0">
        <thead>
          <tr>
            <th>Kode promo</th>
            <th>Diskon</th>
            <th>Berlaku</th>
            <th>Diberikan / Dipakai</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftar as $p): ?>
            <tr>
              <td>
                <b><?= e($p['kode']) ?></b>
                <div class="small"><?= e($p['judul']) ?></div>
                <?php if ($p['untuk_review']): ?>
                  <span class="badge bg-warning text-dark">Hadiah review</span>
                <?php endif ?>
              </td>
              <td><?= $p['diskon_persen'] ?>%</td>
              <td>
                <?php if ($p['berlaku_sampai']): ?>
                  <?= date('d/m/Y', strtotime($p['berlaku_sampai'])) ?>
                <?php else: ?>
                  Tanpa batas
                <?php endif ?>
                <br><small><?= $p['aktif'] ? 'Aktif' : 'Nonaktif' ?></small>
              </td>
              <td><?= $p['diberikan'] ?> / <?= $p['dipakai'] ?></td>
              <td>
                <div class="aksi">
                  <a class="btn btn-sm btn-outline-primary" href="?edit=<?= $p['id'] ?>">Edit</a>
                  <?= delform($p['id']) ?>
                </div>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
<?php foot();
