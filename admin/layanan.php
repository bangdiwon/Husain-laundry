<?php
require '../inc.php';
guard('admin');

// ---------- 1. HAPUS ----------
if (isset($_POST['del'])) {
    try {
        q("DELETE FROM layanan WHERE id=?", [$_POST['del']]);
        flash('Layanan dihapus.');
    } catch (PDOException $e) {
        // Jika masih dipakai, cukup dinonaktifkan
        q("UPDATE layanan SET aktif=0 WHERE id=?", [$_POST['del']]);
        flash('Layanan sudah dipakai transaksi, jadi dinonaktifkan.', 'warning');
    }
    go('layanan.php');
}

// ---------- 2. SIMPAN (tambah / edit) ----------
if (isset($_POST['simpan'])) {
    $d     = array_map('trim', $_POST);
    $id    = (int)$d['id'];
    $aktif = isset($_POST['aktif']) ? 1 : 0;
    $harga = (int)$d['harga'];

    if ($id) {
        q("UPDATE layanan SET nama=?, harga=?, satuan=?, deskripsi=?, aktif=? WHERE id=?",
          [$d['nama'], $harga, $d['satuan'], $d['deskripsi'], $aktif, $id]);
    } else {
        q("INSERT INTO layanan(nama, harga, satuan, deskripsi, aktif) VALUES(?, ?, ?, ?, ?)",
          [$d['nama'], $harga, $d['satuan'], $d['deskripsi'], $aktif]);
    }

    flash('Layanan disimpan.');
    go('layanan.php');
}

// ---------- 3. DATA TAMPILAN ----------
$edit = null;
if (isset($_GET['edit'])) {
    $edit = q("SELECT * FROM layanan WHERE id=?", [$_GET['edit']])->fetch();
}

$daftar = q("SELECT * FROM layanan ORDER BY aktif DESC, nama")->fetchAll();

head('Layanan & Harga', 'admin');
?>
<div class="row g-4">

  <!-- Form tambah / edit -->
  <div class="col-xl-4">
    <div class="card p-3">
      <h5><?= $edit ? 'Edit layanan' : 'Tambah layanan' ?></h5>

      <form method="post">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

        <input name="nama" class="form-control mb-2" placeholder="Nama layanan" required
               value="<?= e($edit['nama'] ?? '') ?>">

        <div class="input-group mb-2">
          <input type="number" name="harga" class="form-control" placeholder="Harga" required
                 value="<?= e($edit['harga'] ?? '') ?>">

          <select name="satuan" class="form-select">
            <?php foreach (['kg', 'pcs'] as $s): ?>
              <option <?= sel($edit['satuan'] ?? '', $s) ?>><?= $s ?></option>
            <?php endforeach ?>
          </select>
        </div>

        <input name="deskripsi" class="form-control mb-2" placeholder="Deskripsi"
               value="<?= e($edit['deskripsi'] ?? '') ?>">

        <div class="form-check mb-3">
          <input type="checkbox" name="aktif" class="form-check-input" id="aktif"
                 <?= ($edit['aktif'] ?? 1) ? 'checked' : '' ?>>
          <label for="aktif" class="form-check-label">Aktif (tampil ke pelanggan)</label>
        </div>

        <button name="simpan" class="btn btn-primary">Simpan</button>
        <?php if ($edit): ?>
          <a href="layanan.php" class="btn btn-light">Batal</a>
        <?php endif ?>
      </form>
    </div>
  </div>

  <!-- Daftar layanan -->
  <div class="col-xl-8">
    <div class="card table-responsive">
      <table class="table rtable mb-0">
        <thead>
          <tr>
            <th>Layanan</th>
            <th>Harga</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftar as $l): ?>
            <tr>
              <td>
                <b><?= e($l['nama']) ?></b>
                <div class="small text-muted"><?= e($l['deskripsi']) ?></div>
              </td>
              <td><?= rp($l['harga']) ?> / <?= $l['satuan'] ?></td>
              <td><?= $l['aktif'] ? 'Aktif' : 'Nonaktif' ?></td>
              <td>
                <div class="aksi">
                  <a class="btn btn-sm btn-outline-primary" href="?edit=<?= $l['id'] ?>">Edit</a>
                  <?= delform($l['id']) ?>
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
