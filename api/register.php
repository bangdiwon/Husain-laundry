<?php
require 'inc.php';

// ---------- Proses pendaftaran ----------
if ($_POST) {
    $d = array_map('trim', $_POST);

    $emailSudahAda = q("SELECT id FROM users WHERE email=?", [$d['email']])->fetch();

    if (strlen($d['password']) < 6) {
        flash('Password minimal 6 karakter.', 'danger');

    } elseif ($emailSudahAda) {
        flash('Email sudah terdaftar. Gunakan email lain atau masuk.', 'danger');

    } else {
        q("INSERT INTO users(nama, email, password, no_hp, alamat, role)
           VALUES(?, ?, ?, ?, ?, 'customer')",
          [
              $d['nama'],
              $d['email'],
              password_hash($d['password'], PASSWORD_DEFAULT),
              $d['no_hp'],
              $d['alamat'],
          ]);

        notif($pdo->lastInsertId(), 'Selamat datang di Husain Laundry! Pantau status cucian Anda di sini.');

        flash('Pendaftaran berhasil. Silakan masuk.');
        go('login.php');
    }
}

head('Daftar', null, 'bare');
?>
<div class="auth">

  <!-- Sisi kiri: ajakan -->
  <div class="auth-art">
    <h1 class="mb-3"><i class="bi bi-droplet-half text-warning"></i> Husain Laundry</h1>
    <p class="lead" style="color:#cfe0f7">Gratis dan selesai dalam satu menit.</p>

    <ul class="p-0 mt-3">
      <li><i class="bi bi-check-circle-fill"></i>Diskon member 5% setelah 5 transaksi</li>
      <li><i class="bi bi-check-circle-fill"></i>Riwayat cucian tersimpan rapi</li>
      <li><i class="bi bi-check-circle-fill"></i>Kirim saran dan keluhan langsung ke kami</li>
    </ul>
  </div>

  <!-- Sisi kanan: form -->
  <div class="auth-form">
    <div>
      <h2>Buat akun pelanggan</h2>
      <p class="text-muted mb-4">Isi data di bawah untuk mulai.</p>

      <?= flash() ?>

      <form method="post" class="row g-3">

        <div class="col-12">
          <label class="form-label fw-semibold">Nama lengkap</label>
          <input name="nama" class="form-control" required
                 value="<?= e($_POST['nama'] ?? '') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Email</label>
          <input type="email" name="email" class="form-control" required
                 value="<?= e($_POST['email'] ?? '') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">No. WhatsApp</label>
          <input name="no_hp" class="form-control" required
                 value="<?= e($_POST['no_hp'] ?? '') ?>">
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Alamat</label>
          <textarea name="alamat" class="form-control"
                    rows="2"><?= e($_POST['alamat'] ?? '') ?></textarea>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Password</label>
          <input type="password" name="password" class="form-control"
                 placeholder="Minimal 6 karakter" required>
        </div>

        <div class="col-12">
          <button class="btn btn-primary w-100 py-2">Buat akun</button>
        </div>

      </form>

      <p class="mt-4 mb-0 text-center">
        Sudah punya akun? <a href="login.php" class="fw-semibold">Masuk</a>
      </p>
    </div>
  </div>

</div>
<?php foot();
