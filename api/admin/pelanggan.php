<?php
require '../inc.php';
guard('admin');

// ---------- 1. HAPUS ----------
if (isset($_POST['del'])) {
    q("DELETE FROM users WHERE id=? AND role='customer'", [$_POST['del']]);
    flash('Pelanggan dihapus.');
    go('pelanggan.php');
}

// ---------- 2. SIMPAN (tambah / edit) ----------
if (isset($_POST['simpan'])) {
    $d  = array_map('trim', $_POST);
    $id = (int)$d['id'];

    // Email tidak boleh sama dengan akun lain
    $dipakai = q("SELECT id FROM users WHERE email=? AND id<>?", [$d['email'], $id])->fetch();

    if ($dipakai) {
        flash('Email sudah terdaftar.', 'danger');
        go('pelanggan.php' . ($id ? "?edit=$id" : ''));
    }

    if ($id) {
        q("UPDATE users SET nama=?, email=?, no_hp=?, alamat=? WHERE id=? AND role='customer'",
          [$d['nama'], $d['email'], $d['no_hp'], $d['alamat'], $id]);

        if ($d['password'] !== '') {
            q("UPDATE users SET password=? WHERE id=?",
              [password_hash($d['password'], PASSWORD_DEFAULT), $id]);
        }
    } else {
        $password = $d['password'] ?: '123456';     // password bawaan jika dikosongkan

        q("INSERT INTO users(nama, email, password, no_hp, alamat, role)
           VALUES(?, ?, ?, ?, ?, 'customer')",
          [$d['nama'], $d['email'], password_hash($password, PASSWORD_DEFAULT), $d['no_hp'], $d['alamat']]);
    }

    flash('Data pelanggan disimpan.');
    go('pelanggan.php');
}

// ---------- 3. DATA TAMPILAN ----------
$edit = null;
if (isset($_GET['edit'])) {
    $edit = q("SELECT * FROM users WHERE id=? AND role='customer'", [$_GET['edit']])->fetch();
}

$cari = trim($_GET['cari'] ?? '');
$kata = "%$cari%";

$daftar = q("SELECT u.*, (SELECT COUNT(*) FROM transaksi WHERE user_id = u.id) jumlah
             FROM users u
             WHERE role = 'customer'
               AND (nama LIKE ? OR email LIKE ? OR no_hp LIKE ?)
             ORDER BY id DESC", [$kata, $kata, $kata])->fetchAll();

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

        <input type="email" name="email" class="form-control mb-2" placeholder="Email" required
               value="<?= e($edit['email'] ?? '') ?>">

        <input name="no_hp" class="form-control mb-2" placeholder="No. WhatsApp (08xx)"
               value="<?= e($edit['no_hp'] ?? '') ?>">

        <textarea name="alamat" class="form-control mb-2" rows="2"
                  placeholder="Alamat"><?= e($edit['alamat'] ?? '') ?></textarea>

        <input type="password" name="password" class="form-control mb-3"
               placeholder="<?= $edit ? 'Password baru (opsional)' : 'Password (default 123456)' ?>">

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
      <input name="cari" class="form-control" placeholder="Cari nama, email, atau HP"
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
              <td>
                <?= e($p['email']) ?>
                <div class="small"><?= e($p['no_hp']) ?></div>
              </td>
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
