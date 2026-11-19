<?php
require '../inc.php';
guard('admin');

// ---------- 1. HAPUS ----------
if (isset($_POST['del'])) {
    q("DELETE FROM promo WHERE id=?", [$_POST['del']]);
    flash('Promo dihapus.');
    go('promo.php');
}

// ---------- 2. SIMPAN (tambah / edit) ----------
if (isset($_POST['simpan'])) {
    $d      = array_map('trim', $_POST);
    $id     = (int)$d['id'];
    $aktif  = isset($_POST['aktif']) ? 1 : 0;
    $diskon = (int)$d['diskon_persen'];
    $sampai = $d['berlaku_sampai'] ?: null;      // kosong = tanpa batas waktu

    if ($id) {
        q("UPDATE promo SET judul=?, deskripsi=?, diskon_persen=?, berlaku_sampai=?, aktif=? WHERE id=?",
          [$d['judul'], $d['deskripsi'], $diskon, $sampai, $aktif, $id]);
    } else {
        q("INSERT INTO promo(judul, deskripsi, diskon_persen, berlaku_sampai, aktif)
           VALUES(?, ?, ?, ?, ?)",
          [$d['judul'], $d['deskripsi'], $diskon, $sampai, $aktif]);
    }

    flash('Promo disimpan.');
    go('promo.php');
}

// ---------- 3. DATA TAMPILAN ----------
$edit = null;
if (isset($_GET['edit'])) {
    $edit = q("SELECT * FROM promo WHERE id=?", [$_GET['edit']])->fetch();
}

$daftar = q("SELECT * FROM promo ORDER BY id DESC")->fetchAll();

head('Promosi', 'admin');
?>
<div class="row g-4">

  <!-- Form tambah / edit -->
  <div class="col-xl-4">
    <div class="card p-3">
      <h5><?= $edit ? 'Edit promo' : 'Buat promo' ?></h5>

      <form method="post">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

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
    </div>
  </div>

  <!-- Daftar promo -->
  <div class="col-xl-8">
    <div class="card table-responsive">
      <table class="table rtable mb-0">
        <thead>
          <tr>
            <th>Promo</th>
            <th>Diskon</th>
            <th>Berlaku</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftar as $p): ?>
            <tr>
              <td>
                <b><?= e($p['judul']) ?></b>
                <div class="small text-muted"><?= e($p['deskripsi']) ?></div>
              </td>
              <td><?= $p['diskon_persen'] ?>%</td>
              <td>
                <?= $p['berlaku_sampai'] ? date('d/m/Y', strtotime($p['berlaku_sampai'])) : 'Tanpa batas' ?>
                <br><small><?= $p['aktif'] ? 'Aktif' : 'Nonaktif' ?></small>
              </td>
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
