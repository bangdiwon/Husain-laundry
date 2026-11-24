<?php
require '../inc.php';
guard('admin');

// ---------- 1. HAPUS ----------
if (isset($_POST['del'])) {
    try {
        q("DELETE FROM pelanggan WHERE id=?", [$_POST['del']]);
        flash('Pelanggan dihapus.');
    } catch (PDOException $e) {
        // Pelanggan yang sudah punya transaksi tidak boleh dihapus (riwayat harus utuh)
        flash('Pelanggan ini sudah punya transaksi, jadi tidak bisa dihapus.', 'warning');
    }
    go('pelanggan.php');
}

// ---------- 2. SIMPAN (tambah / edit) ----------
if (isset($_POST['simpan'])) {
    $d  = array_map('trim', $_POST);
    $id = (int)$d['id'];

    if ($id) {
        q("UPDATE pelanggan SET nama=?, no_hp=?, alamat=? WHERE id=?",
          [$d['nama'], $d['no_hp'], $d['alamat'], $id]);
    } else {
        q("INSERT INTO pelanggan(nama, no_hp, alamat) VALUES(?, ?, ?)",
          [$d['nama'], $d['no_hp'], $d['alamat']]);
    }

    flash('Data pelanggan disimpan.');
    go('pelanggan.php');
}

// ---------- 3. DATA TAMPILAN ----------
$edit = null;
if (isset($_GET['edit'])) {
    $edit = q("SELECT * FROM pelanggan WHERE id=?", [$_GET['edit']])->fetch();
}

$cari = trim($_GET['cari'] ?? '');
$kata = "%$cari%";

$daftar = q("SELECT p.*, (SELECT COUNT(*) FROM transaksi WHERE pelanggan_id = p.id) jumlah
             FROM pelanggan p
             WHERE nama LIKE ? OR no_hp LIKE ?
             ORDER BY id DESC", [$kata, $kata])->fetchAll();

head('Data Pelanggan', 'admin');
?>
<div class="row g-4">

  <!-- Form tambah / edit -->
  <div class="col-xl-4">
    <div class="card p-3">
      <h5><?= $edit ? 'Edit pelanggan' : 'Tambah pelanggan' ?></h5>

      <form method="post">
        <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">

        <input name="nama" class="form-control mb-2" placeholder="Nama" required
               value="<?= e($edit['nama'] ?? '') ?>">

        <input name="no_hp" class="form-control mb-2" placeholder="No. WhatsApp (08xx)"
               value="<?= e($edit['no_hp'] ?? '') ?>">

        <textarea name="alamat" class="form-control mb-3" rows="2"
                  placeholder="Alamat"><?= e($edit['alamat'] ?? '') ?></textarea>

        <button name="simpan" class="btn btn-primary">Simpan</button>
        <?php if ($edit): ?>
          <a href="pelanggan.php" class="btn btn-light">Batal</a>
        <?php endif ?>
      </form>
    </div>
  </div>

  <!-- Daftar pelanggan -->
  <div class="col-xl-8">

    <form class="d-flex gap-2 mb-2">
      <input name="cari" class="form-control" placeholder="Cari nama atau nomor HP"
             value="<?= e($cari) ?>">
      <button class="btn btn-primary">Cari</button>
    </form>

    <div class="card table-responsive">
      <table class="table rtable mb-0">
        <thead>
          <tr>
            <th>Nama</th>
            <th>Kontak</th>
            <th>Transaksi</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftar as $p): ?>
            <tr>
              <td>
                <b><?= e($p['nama']) ?></b>
                <div class="small text-muted"><?= e($p['alamat']) ?></div>
              </td>
              <td><?= e($p['no_hp']) ?></td>
              <td><?= $p['jumlah'] ?></td>
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
