<?php
require '../inc.php';
guard('customer');

$uid = $_SESSION['uid'];

// ---------- Simpan perubahan profil ----------
if ($_POST) {
    $d = array_map('trim', $_POST);

    $emailDipakai = q("SELECT id FROM users WHERE email=? AND id<>?", [$d['email'], $uid])->fetch();

    if ($emailDipakai) {
        flash('Email sudah dipakai akun lain.', 'danger');

    } else {
        q("UPDATE users SET nama=?, email=?, no_hp=?, alamat=? WHERE id=?",
          [$d['nama'], $d['email'], $d['no_hp'], $d['alamat'], $uid]);

        // Password hanya diganti jika kolomnya diisi
        if ($d['password'] !== '') {
            if (strlen($d['password']) < 6) {
                flash('Password baru minimal 6 karakter.', 'danger');
                go('profil.php');
            }

            q("UPDATE users SET password=? WHERE id=?",
              [password_hash($d['password'], PASSWORD_DEFAULT), $uid]);
        }

        $_SESSION['nama'] = $d['nama'];

        flash('Profil diperbarui.');
        go('profil.php');
    }
}

$user = q("SELECT * FROM users WHERE id=?", [$uid])->fetch();

head('Profil', 'customer');
?>
<div class="row">
  <div class="col-md-7">
    <div class="card p-4">
      <h4>Profil saya</h4>

      <form method="post" class="row g-3">

        <div class="col-12">
          <label class="form-label">Nama</label>
          <input name="nama" class="form-control" required value="<?= e($user['nama']) ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required
                 value="<?= e($user['email']) ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label">No. WhatsApp</label>
          <input name="no_hp" class="form-control" value="<?= e($user['no_hp']) ?>">
        </div>

        <div class="col-12">
          <label class="form-label">Alamat</label>
          <textarea name="alamat" class="form-control" rows="2"><?= e($user['alamat']) ?></textarea>
        </div>

        <div class="col-12">
          <label class="form-label">Password baru (kosongkan jika tidak diganti)</label>
          <input type="password" name="password" class="form-control">
        </div>

        <div class="col-12">
          <button class="btn btn-primary">Simpan perubahan</button>
        </div>

      </form>
    </div>
  </div>
</div>
<?php foot();
