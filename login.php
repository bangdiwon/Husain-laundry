<?php
require 'inc.php';

// Admin yang sudah login langsung ke dashboard
if (!empty($_SESSION['role'])) {
    go('admin/index.php');
}

// ---------- Proses login admin ----------
if ($_POST) {
    $admin = q("SELECT * FROM admin WHERE email=?", [trim($_POST['email'])])->fetch();

    if ($admin && password_verify($_POST['password'], $admin['password'])) {
        session_regenerate_id(true);         // ganti ID sesi agar aman

        $_SESSION = [
            'uid'   => $admin['id'],
            'role'  => 'admin',
            'nama'  => $admin['nama'],
            'aktif' => time(),
            'csrf'  => bin2hex(random_bytes(16)),
        ];

        go('admin/index.php');
    }

    flash('Email atau password salah. Periksa kembali lalu coba lagi.', 'danger');
}

head('Login Admin', null, 'bare');
?>
<div class="auth">

  <!-- Sisi kiri -->
  <div class="auth-art">
    <h1 class="mb-3"><i class="bi bi-droplet-half text-warning"></i> Husain Laundry</h1>
    <p class="lead" style="color:#cfe0f7">Panel admin E-CRM.</p>

    <ul class="p-0 mt-3">
      <li><i class="bi bi-check-circle-fill"></i>Catat pesanan dan ubah status cucian</li>
      <li><i class="bi bi-check-circle-fill"></i>Kelola pelanggan, layanan, dan kode promo</li>
      <li><i class="bi bi-check-circle-fill"></i>Pantau review dan cetak laporan</li>
    </ul>
  </div>

  <!-- Sisi kanan: form -->
  <div class="auth-form">
    <div>
      <h2>Login admin</h2>
      <p class="text-muted mb-4">Halaman ini khusus pengelola Husain Laundry.</p>

      <?= flash() ?>

      <form method="post">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="email" class="form-control mb-3" required autofocus>

        <label class="form-label fw-semibold">Password</label>
        <input type="password" name="password" class="form-control mb-4" required>

        <button class="btn btn-primary w-100 py-2">Masuk</button>
      </form>

      <p class="text-center mt-4 mb-0">
        <a href="index.php" class="small text-muted">
          <i class="bi bi-arrow-left"></i> Kembali ke website
        </a>
      </p>
    </div>
  </div>

</div>
<?php foot();
