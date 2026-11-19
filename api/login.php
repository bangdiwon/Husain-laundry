<?php
require 'inc.php';

// Sudah login: langsung ke dashboard sesuai peran
if (!empty($_SESSION['role'])) {
    go($_SESSION['role'] . '/index.php');
}

// ---------- Proses login ----------
if ($_POST) {
    $user = q("SELECT * FROM users WHERE email=?", [trim($_POST['email'])])->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        session_regenerate_id(true);         // ganti ID sesi agar aman

        $_SESSION = [
            'uid'   => $user['id'],
            'role'  => $user['role'],
            'nama'  => $user['nama'],
            'aktif' => time(),
            'csrf'  => bin2hex(random_bytes(16)),
        ];

        go($user['role'] . '/index.php');
    }

    flash('Email atau password salah. Periksa kembali lalu coba lagi.', 'danger');
}

head('Masuk', null, 'bare');
?>
<div class="auth">

  <!-- Sisi kiri: ajakan -->
  <div class="auth-art">
    <h1 class="mb-3"><i class="bi bi-droplet-half text-warning"></i> Husain Laundry</h1>
    <p class="lead" style="color:#cfe0f7">
      Pantau cucian, lihat riwayat, dan dapatkan promo dalam satu akun.
    </p>

    <ul class="p-0 mt-3">
      <li><i class="bi bi-check-circle-fill"></i>Status cucian diperbarui langsung</li>
      <li><i class="bi bi-check-circle-fill"></i>Notifikasi saat cucian siap diambil</li>
      <li><i class="bi bi-check-circle-fill"></i>Diskon untuk pelanggan setia</li>
    </ul>
  </div>

  <!-- Sisi kanan: form -->
  <div class="auth-form">
    <div>
      <h2>Selamat datang kembali</h2>
      <p class="text-muted mb-4">Masuk sebagai pelanggan atau admin.</p>

      <?= flash() ?>

      <form method="post">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="email" class="form-control mb-3" required autofocus>

        <label class="form-label fw-semibold">Password</label>
        <input type="password" name="password" class="form-control mb-4" required>

        <button class="btn btn-primary w-100 py-2">Masuk</button>
      </form>

      <p class="mt-4 mb-0 text-center">
        Belum punya akun?
        <a href="register.php" class="fw-semibold">Daftar gratis</a>
      </p>
      <p class="text-center mt-2">
        <a href="index.php" class="small text-muted">Kembali ke beranda</a>
      </p>
    </div>
  </div>

</div>
<?php foot();
